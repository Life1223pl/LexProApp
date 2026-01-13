<?php

namespace App\EventSubscriber;

use App\Entity\Czynnosc;
use App\Entity\CzynnoscUczestnik;
use App\Entity\HistoryLog;
use App\Entity\Postepowanie;
use App\Entity\PostepowanieOsoba;
use App\Entity\PostepowaniePracownik;
use App\Entity\Pracownik;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class HistoryLogDoctrineSubscriber
{

    private const ALLOWED = [
        Postepowanie::class,
        Czynnosc::class,
        PostepowanieOsoba::class,
        PostepowaniePracownik::class,
        CzynnoscUczestnik::class,
    ];

    /**
     * Pola techniczne
     */
    private const IGNORE_FIELDS = [
        'createdAt',
        'updatedAt',
        'password',
    ];

    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack,
    ) {}

    /**
     * metoda wołana przez Doctrine (tag w services.yaml):
     * - { name: doctrine.event_listener, event: onFlush, method: onFlush }
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em  = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        $user  = $this->security->getUser();
        $actor = $user instanceof Pracownik ? $user : null;

        $request = $this->requestStack->getCurrentRequest();
        $ip = $request?->getClientIp();
        $ua = $request?->headers->get('User-Agent');

        // INSERTS
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$this->shouldLog($entity)) {
                continue;
            }

            $changeset = $this->filterChangeset($uow->getEntityChangeSet($entity));

            $log = $this->buildLog(
                $entity,
                HistoryLog::ACTION_CREATE,
                $actor,
                $ip,
                $ua,
                $changeset
            );

            $em->persist($log);
            $uow->computeChangeSet($em->getClassMetadata(HistoryLog::class), $log);
        }

        // UPDATES
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$this->shouldLog($entity)) {
                continue;
            }

            $changeset = $this->filterChangeset($uow->getEntityChangeSet($entity));
            if (empty($changeset)) {
                continue;
            }

            $log = $this->buildLog(
                $entity,
                HistoryLog::ACTION_UPDATE,
                $actor,
                $ip,
                $ua,
                $changeset
            );

            $em->persist($log);
            $uow->computeChangeSet($em->getClassMetadata(HistoryLog::class), $log);
        }

        // DELETES
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if (!$this->shouldLog($entity)) {
                continue;
            }

            $changeset = $this->filterChangeset($uow->getEntityChangeSet($entity));


            if (empty($changeset)) {
                $changeset = $this->buildDeleteSnapshot($entity);
            }

            $log = $this->buildLog(
                $entity,
                HistoryLog::ACTION_DELETE,
                $actor,
                $ip,
                $ua,
                $changeset
            );

            $em->persist($log);
            $uow->computeChangeSet($em->getClassMetadata(HistoryLog::class), $log);
        }
    }


    private function realClass(object $entity): string
    {
        $class = $entity::class;


        if (str_contains($class, 'Proxies\\__CG__\\')) {
            $parent = get_parent_class($entity);
            return $parent ?: $class;
        }

        return $class;
    }

    private function shouldLog(object $entity): bool
    {
        if ($entity instanceof HistoryLog) {
            return false;
        }

        $realClass = $this->realClass($entity);

        return in_array($realClass, self::ALLOWED, true);
    }

    private function buildLog(
        object $entity,
        string $action,
        ?Pracownik $actor,
        ?string $ip,
        ?string $ua,
        ?array $changeset
    ): HistoryLog {
        $log = new HistoryLog();
        $log->setAction($action);
        $log->setUser($actor);
        $log->setIp($ip);
        $log->setUserAgent($ua);

        $realClass = $this->realClass($entity);
        $log->setEntityClass($realClass);

        $log->setEntityId(method_exists($entity, 'getId') ? $entity->getId() : null);

        $postepowanie = $this->resolvePostepowanie($entity, $realClass);
        $log->setPostepowanie($postepowanie);

        if ($changeset !== null) {
            $log->setChanges($this->normalizeChangeset($changeset));
        }

        return $log;
    }

    /**
     * przypinamy log do właściwego Postępowania.
     */
    private function resolvePostepowanie(object $entity, string $realClass): ?Postepowanie
    {
        if ($entity instanceof Postepowanie || $realClass === Postepowanie::class) {
            return $entity instanceof Postepowanie ? $entity : null;
        }

        if ($entity instanceof Czynnosc || $realClass === Czynnosc::class) {
            return $entity->getPostepowanie();
        }

        if ($entity instanceof PostepowanieOsoba || $realClass === PostepowanieOsoba::class) {
            return $entity->getPostepowanie();
        }

        if ($entity instanceof PostepowaniePracownik || $realClass === PostepowaniePracownik::class) {
            return $entity->getPostepowanie();
        }

        if ($entity instanceof CzynnoscUczestnik || $realClass === CzynnoscUczestnik::class) {
            return $entity->getCzynnosc()->getPostepowanie();
        }

        // fallback jeśli dodam encję z getPostepowanie()
        if (method_exists($entity, 'getPostepowanie')) {
            $p = $entity->getPostepowanie();
            return $p instanceof Postepowanie ? $p : null;
        }

        return null;
    }


    private function buildDeleteSnapshot(object $entity): array
    {
        if ($entity instanceof PostepowanieOsoba) {
            return [

                'osoba' => [null, $entity->getOsoba()],
                'rola'  => [null, $entity->getRola()],
            ];
        }

        if ($entity instanceof Czynnosc) {
            return [
                'nazwa' => [null, method_exists($entity, 'getNazwa') ? $entity->getNazwa() : null],
                'data'  => [null, method_exists($entity, 'getData') ? $entity->getData() : null],
            ];
        }

        return [];
    }

    private function filterChangeset(array $changeset): array
    {
        foreach (self::IGNORE_FIELDS as $field) {
            unset($changeset[$field]);
        }
        return $changeset;
    }

    private function normalizeChangeset(array $changeset): array
    {
        $out = [];

        foreach ($changeset as $field => $pair) {
            // Doctrine daje [old, new]
            $old = $pair[0] ?? null;
            $new = $pair[1] ?? null;

            $out[$field] = [
                'old' => $this->normalizeValue($old),
                'new' => $this->normalizeValue($new),
            ];
        }

        return $out;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_scalar($value)) {
            return $value;
        }

        if (is_object($value)) {
            if (method_exists($value, 'getId')) {
                return [
                    'class' => $this->realClass($value),
                    'id' => $value->getId(),
                    'label' => $this->makeLabel($value),
                ];
            }

            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            return $this->realClass($value);
        }

        if (is_array($value)) {
            return $value;
        }


        return (string) $value;
    }
    private function makeLabel(object $value): ?string
    {
        // jeśli encja ma __toString()
        if (method_exists($value, '__toString')) {
            $s = trim((string) $value);
            if ($s !== '' && $s !== 'Object') {
                return $s;
            }
        }


        $first = null;
        $last  = null;

        if (method_exists($value, 'getImie')) {
            $first = $value->getImie();
        } elseif (method_exists($value, 'getFirstName')) {
            $first = $value->getFirstName();
        }

        if (method_exists($value, 'getNazwisko')) {
            $last = $value->getNazwisko();
        } elseif (method_exists($value, 'getLastName')) {
            $last = $value->getLastName();
        }

        $full = trim(trim((string) $first) . ' ' . trim((string) $last));
        if ($full !== '') {
            return $full;
        }


        if (method_exists($value, 'getId') && $value->getId() !== null) {
            $short = (new \ReflectionClass($value))->getShortName();
            return $short . '#' . $value->getId();
        }

        return null;
    }

}
