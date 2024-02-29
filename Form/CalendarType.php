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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CalendarType extends AbstractType {
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options): void {
		//Build form
		$builder
			->add('calendar', ChoiceType::class, ['attr' => ['placeholder' => 'Your calendar'], 'choice_translation_domain' => false, 'expanded' => true, 'multiple' => true, 'choices' => $options['calendar_choices']/*, 'data' => $options['calendar_default']*/, 'choice_attr' => ['class' => 'row']])
			->add('submit', SubmitType::class, ['label' => 'Send', 'attr' => ['class' => 'submit']])
			->add('refresh', SubmitType::class, ['label' => 'Refresh', 'attr' => ['class' => 'submit']])
			->add('add', SubmitType::class, ['label' => 'Add', 'attr' => ['class' => 'submit']])
			->add('delete', SubmitType::class, ['label' => 'Delete', 'attr' => ['class' => 'submit']])
			->add('unlink', SubmitType::class, ['label' => 'Unlink', 'attr' => ['class' => 'submit']]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver): void {
		//Set defaults
		$resolver->setDefaults(['error_bubbling' => true, 'calendar_choices' => [], /*'calendar_default' => ['primary']*/]);

		//Add calendar choices
		$resolver->setAllowedTypes('calendar_choices', 'array');

		//Add calendar default
		#$resolver->setAllowedTypes('calendar_default', 'integer');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName(): string {
		return 'calendar_form';
	}
}
