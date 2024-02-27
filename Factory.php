<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys AirBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Repository\RepositoryFactory;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use Rapsys\PackBundle\Util\SluggerUtil;

/**
 * This factory is used to create default repository objects for entities at runtime.
 */
final class Factory implements RepositoryFactory {
	/**
	 * The list of EntityRepository instances
	 */
	private array $repositoryList = [];

	/**
	 * Initializes a new RepositoryFactory instance
	 *
	 * @param RequestStack $request The request stack
	 * @param RouterInterface $router The router instance
	 * @param SluggerUtil $slugger The SluggerUtil instance
	 * @param TranslatorInterface $translator The TranslatorInterface instance
	 * @param string $locale The current locale
	 * @param array $languages The languages list
	 */
	public function __construct(private RequestStack $request, private RouterInterface $router, private SluggerUtil $slugger, private TranslatorInterface $translator, private string $locale, private array $languages) {
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRepository(EntityManagerInterface $entityManager, mixed $entityName): ObjectRepository {
		//Set repository hash
		$repositoryHash = $entityManager->getClassMetadata($entityName)->getName() . spl_object_hash($entityManager);

		//With entity repository instance
		if (isset($this->repositoryList[$repositoryHash])) {
			//Return existing entity repository instance
			return $this->repositoryList[$repositoryHash];
		}

		//Store and return created entity repository instance
		return $this->repositoryList[$repositoryHash] = $this->createRepository($entityManager, $entityName);
	}

	/**
	 * Create a new repository instance for an entity class
	 *
	 * @param EntityManagerInterface $entityManager The EntityManager instance.
	 * @param string $entityName The name of the entity.
	 */
	private function createRepository(EntityManagerInterface $entityManager, string $entityName): ObjectRepository {
		//Get class metadata
		$metadata = $entityManager->getClassMetadata($entityName);

		//Get repository class
		$repositoryClass = $metadata->customRepositoryClassName ?: $entityManager->getConfiguration()->getDefaultRepositoryClassName();

		//Set to current locale
		//XXX: current request is not yet populated in constructor
		$this->locale = $this->request->getCurrentRequest()->getLocale() ?? $this->locale;

		//Return repository class instance
		//XXX: router, slugger, translator, languages and locale arguments will be ignored by default
		return new $repositoryClass($entityManager, $metadata, $this->router, $this->slugger, $this->translator, $this->locale, $this->languages);
	}
}
