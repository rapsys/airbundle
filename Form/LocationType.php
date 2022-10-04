<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys AirBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use Rapsys\AirBundle\Entity\Location;

/**
 * {@inheritdoc}
 */
class LocationType extends AbstractType {
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		return $builder
			->add('title', TextType::class, ['attr' => ['placeholder' => 'Your title'], 'constraints' => [new NotBlank(['message' => 'Please provide your title'])]])
			->add('description', TextareaType::class, ['attr' => ['placeholder' => 'Your description', 'cols' => 50, 'rows' => 15], 'required' => false])
			->add('address', TextType::class, ['attr' => ['placeholder' => 'Your address'], 'constraints' => [new NotBlank(['message' => 'Please provide your address'])]])
			->add('zipcode', NumberType::class, ['attr' => ['placeholder' => 'Your zipcode'], 'html5' => true, 'constraints' => [new NotBlank(['message' => 'Please provide your zipcode'])]])
			->add('city', TextType::class, ['attr' => ['placeholder' => 'Your city'], 'constraints' => [new NotBlank(['message' => 'Please provide your city'])]])
			->add('latitude', NumberType::class, ['attr' => ['placeholder' => 'Your latitude', 'step' => 0.000001], 'html5' => true, 'scale' => 6, 'constraints' => [new NotBlank(['message' => 'Please provide your latitude'])]])
			->add('longitude', NumberType::class, ['attr' => ['placeholder' => 'Your longitude', 'step' => 0.000001], 'html5' => true, 'scale' => 6, 'constraints' => [new NotBlank(['message' => 'Please provide your longitude'])]])
			->add('indoor', CheckboxType::class, ['attr' => ['placeholder' => 'Your indoor'], 'required' => false])
			->add('hotspot', CheckboxType::class, ['attr' => ['placeholder' => 'Your hotspot'], 'required' => false])
			->add('submit', SubmitType::class, ['label' => 'Send', 'attr' => ['class' => 'submit']]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(['data_class' => Location::class, 'error_bubbling' => true]);
	}
}
