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

use Rapsys\AirBundle\Entity\Dance;

/**
 * {@inheritdoc}
 */
class DanceTransformer implements DataTransformerInterface {
	/**
	 * Public constructor
	 *
	 * @param EntityManagerInterface $manager The entity manager
	 */
	public function __construct(private EntityManagerInterface $manager) {
	}

	/**
	 * Transforms a dance object array or collection to an int array
	 *
	 * @param Collection|array $dances The dance instances array
	 * @return array The dance ids
	 */
	public function transform(mixed $dances): mixed {
		//Without dances
		if (null === $dances) {
			return [];
		}

		//With collection instance
		if ($dances instanceof Collection) {
			$dances = $dances->toArray();
		}

		//Return dance ids
		return array_map(function ($d) { return $d->getId(); }, $dances);
	}

	/**
	 * Transforms an int array to a dance object collection
	 *
	 * @param array $ids
	 * @throws TransformationFailedException when object (dance) is not found
	 * @return array The dance instances array
	 */
	public function reverseTransform(mixed $ids): mixed {
		//Without ids
		if ('' === $ids || null === $ids) {
			$ids = [];
			//With ids
		} else {
			$ids = (array) $ids;
		}

		//Iterate on ids
		foreach($ids as $k => $id) {
			//Replace with dance instance
			$ids[$k] = $this->manager->getRepository(Dance::class)->findOneById($id);

			//Without dance
			if (null === $ids[$k]) {
				//Throw exception
				throw new TransformationFailedException(sprintf('Dance with id "%d" does not exist!', $id));
			}
		}

		//Return collection
		return new ArrayCollection($ids);
	}
}
