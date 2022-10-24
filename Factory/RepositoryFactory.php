<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys AirBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Repository\RepositoryFactory as RepositoryFactoryInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use Rapsys\PackBundle\Util\SluggerUtil;

/**
 * This factory is used to create default repository objects for entities at runtime.
 */
final class RepositoryFactory implements RepositoryFactoryInterface {
	/**
	 * The list of EntityRepository instances
	 *
	 * @var ObjectRepository[]
	 */
	private array $repositoryList = [];

	/**
	 * The list of languages
	 *
	 * @var string[]
	 */
	private array $languages = [];

	/**
	 * The current locale
	 *
	 * @var string
	 */
	private string $locale;

	/**
	 * The RouterInterface instance
	 *
	 * @var RouterInterface
	 */
	private RouterInterface $router;

	/**
	 * The SluggerUtil instance
	 *
	 * @var SluggerUtil
	 */
	private SluggerUtil $slugger;

	/**
	 * The TranslatorInterface instance
	 *
	 * @var TranslatorInterface
	 */
	private TranslatorInterface $translator;

	/**
	 * Initializes a new RepositoryFactory instance
	 *
	 * @param RouterInterface $router The router instance
	 * @param SluggerUtil $slugger The SluggerUtil instance
	 * @param TranslatorInterface $translator The TranslatorInterface instance
	 * @param array $languages The languages list
	 * @param string $locale The current locale
	 */
	public function __construct(RouterInterface $router, SluggerUtil $slugger, TranslatorInterface $translator, array $languages, string $locale) {
		//Set router
		$this->router = $router;

		//Set slugger
		$this->slugger = $slugger;

		//Set translator
		$this->translator = $translator;

		//Set languages
		$this->languages = $languages;

		//Set locale
		$this->locale = $locale;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRepository(EntityManagerInterface $entityManager, $entityName): ObjectRepository {
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

		//Return repository class instance
		//XXX: router, slugger, translator and languages arguments will be ignored by default
		return new $repositoryClass($entityManager, $metadata, $this->router, $this->slugger, $this->translator, $this->languages, $this->locale);
	}
}
