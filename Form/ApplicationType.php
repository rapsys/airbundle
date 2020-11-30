<?php

namespace Rapsys\AirBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Rapsys\AirBundle\Entity\User;
use Rapsys\AirBundle\Entity\Slot;
use Rapsys\AirBundle\Entity\Location;

class ApplicationType extends AbstractType {
	//Doctrine instance
	private $doctrine;

	//Translator instance
	protected $translator;

	public function __construct(RegistryInterface $doctrine, TranslatorInterface $translator) {
		$this->doctrine = $doctrine;
		$this->translator = $translator;
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		//Retrieve translated slot
		#$slots = $this->doctrine->getRepository(Slot::class)->findAllWithTranslatedTitle($this->translator);

		//Create base form
		$form = $builder
			->add('location', EntityType::class, ['class' => 'RapsysAirBundle:Location', 'attr' => ['placeholder' => 'Your location'], 'choice_translation_domain' => true, 'constraints' => [new NotBlank(['message' => 'Please provide your location'])], 'data' => $options['location']])
			->add('date', DateType::class, ['attr' => ['placeholder' => 'Your date', 'class' => 'date'], 'html5' => true, 'input' => 'datetime', 'widget' => 'single_text', 'format' => 'yyyy-MM-dd', 'data' => new \DateTime('+7 day'), 'constraints' => [new NotBlank(['message' => 'Please provide your date']), new Date(['message' => 'Your date doesn\'t seems to be valid'])]])
			#->add('slot', ChoiceType::class, ['attr' => ['placeholder' => 'Your slot'], 'constraints' => [new NotBlank(['message' => 'Please provide your slot'])], 'choices' => $slots, 'data' => $options['slot']])
			->add('slot', EntityType::class, ['class' => 'RapsysAirBundle:Slot', 'attr' => ['placeholder' => 'Your slot'], 'constraints' => [new NotBlank(['message' => 'Please provide your slot'])], 'choice_translation_domain' => true, 'data' => $options['slot']])
			->add('submit', SubmitType::class, ['label' => 'Send', 'attr' => ['class' => 'submit']]);

		//Add extra user field
		if (!empty($options['admin'])) {
			$users = $this->doctrine->getRepository(User::class)->findAllWithTranslatedGroupAndTitle($this->translator);
			$form->add('user', ChoiceType::class, ['attr' => ['placeholder' => 'Your user'], 'constraints' => [new NotBlank(['message' => 'Please provide your user'])], 'choices' => $users, 'data' => $options['user'], 'choice_translation_domain' => false]);
		}

		//Return form
		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver) {
		//XXX: 1 should be the first user
		$resolver->setDefaults(['error_bubbling' => true, 'admin' => false, 'slot' => null, 'location' => null, 'user' => 1]);
		$resolver->setAllowedTypes('admin', 'boolean');
		$resolver->setAllowedTypes('location', [Location::class, 'null']);
		$resolver->setAllowedTypes('slot', [Slot::class, 'null']);
		$resolver->setAllowedTypes('user', 'integer');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'rapsys_air_application';
	}
}
