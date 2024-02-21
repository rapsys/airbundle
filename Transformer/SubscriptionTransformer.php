<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys AirBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle\Transformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use Rapsys\AirBundle\Entity\User;

/**
 * {@inheritdoc}
 */
class SubscriptionTransformer implements DataTransformerInterface {
	/**
	 * Public constructor
	 *
	 * @param EntityManagerInterface $manager The entity manager
	 */
	public function __construct(private EntityManagerInterface $manager) {
	}

	/**
	 * Transforms a subscription object array or collection to an int array
	 *
	 * @param Subscription $subscriptions The subscription instances array
	 * @return array The subscription ids
	 */
	public function transform($subscriptions) {
		//Without subscriptions
		if (null === $subscriptions) {
			return [];
		}

		//With collection instance
		if ($subscriptions instanceof Collection) {
			$subscriptions = $subscriptions->toArray();
		}

		//Return subscription ids
		return array_map(function ($d) { return $d->getId(); }, $subscriptions);
	}

	/**
	 * Transforms an int array to a subscription object collection
	 *
	 * @param array $ids
	 * @throws TransformationFailedException when object (subscription) is not found
	 * @return array The subscription instances array
	 */
	public function reverseTransform($ids) {
		//Without ids
		if ('' === $ids || null === $ids) {
			$ids = [];
			//With ids
		} else {
			$ids = (array) $ids;
		}

		//Iterate on ids
		foreach($ids as $k => $id) {
			//Replace with subscription instance
			$ids[$k] = $this->manager->getRepository(User::class)->findOneById($id);

			//Without subscription
			if (null === $ids[$k]) {
				//Throw exception
				throw new TransformationFailedException(sprintf('User with id "%d" does not exist!', $id));
			}
		}

		//Return collection
		return new ArrayCollection($ids);
	}
}
