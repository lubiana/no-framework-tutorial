<?php

declare(strict_types=1);


namespace Lubian\NoFramework\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Lubian\NoFramework\Exception\NotFound;
use Lubian\NoFramework\Model\MarkdownPage;

final class DoctrineMarkdownPageRepo implements MarkdownPageRepo
{
    /**
     * @var EntityRepository<MarkdownPage>
     */
    private EntityRepository $repo;
    public function __construct(
        private EntityManagerInterface $entityManager
    ){
        $this->repo = $this->entityManager->getRepository(MarkdownPage::class);
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        usleep(rand(500, 1500) * 1000);
        return $this->repo->findAll();
    }

    public function byId(int $id): MarkdownPage
    {
        usleep(rand(500, 1500) * 1000);
        $page = $this->repo->findOneBy(['id' => $id]);
        if (!$page instanceof MarkdownPage){
            throw new NotFound;
        }
        return $page;
    }

    public function byTitle(string $title): MarkdownPage
    {
        usleep(rand(500, 1500) * 1000);
        $page = $this->repo->findOneBy(['title' => $title]);
        if (!$page instanceof MarkdownPage){
            throw new NotFound;
        }
        return $page;
    }

    public function save(MarkdownPage $page): MarkdownPage
    {
        $this->entityManager->persist($page);
        $this->entityManager->flush();
        return $page;
    }
}