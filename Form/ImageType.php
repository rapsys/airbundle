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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/**
 * {@inheritdoc}
 */
class ImageType extends AbstractType {
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $form, array $options): FormBuilderInterface {
		//With location
		if ($options['location']) {
			$form->add('location', HiddenType::class, ['required' => true]);
		}

		//With user
		if ($options['user']) {
			$form->add('user', HiddenType::class, ['required' => true]);
		}

		//With image
		if ($options['image']) {
			//With image
			$form->add('image', FileType::class, ['attr' => ['placeholder' => 'Your image'], 'constraints' => [new File(['maxSize' => '5M', 'mimeTypes' => ['image/jpeg', 'image/png', 'image/tiff', 'image/webp'], 'mimeTypesMessage' => 'Please upload a valid Image document'])]/*, 'mapped' => false*/, 'required' => $options['delete'] ? false : true]);
		}

		//Add submit
		$form->add('submit', SubmitType::class, ['label' => 'Send', 'attr' => ['class' => 'submit']]);

		//With delete
		if ($options['delete']) {
			//Add delete
			//TODO: add confirm on click ?
			$form->add('delete', SubmitType::class, ['label' => 'Delete', 'attr' => ['class' => 'submit']]);
		}

		//Return form builder
		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver): void {
		//Set defaults
		$resolver->setDefaults(['delete' => true, 'error_bubbling' => true, 'image' => true, 'location' => true, 'user' => true]);

		//Add extra delete option
		$resolver->setAllowedTypes('delete', 'boolean');

		//Add extra image option
		$resolver->setAllowedTypes('image', 'boolean');

		//Add extra location option
		$resolver->setAllowedTypes('location', 'boolean');

		//Add extra user option
		$resolver->setAllowedTypes('user', 'boolean');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName(): string {
		return 'rapsysair_image';
	}
}
