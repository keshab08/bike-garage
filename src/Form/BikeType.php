<?php

namespace App\Form;

use App\Model\Category;
use App\Model\Condition;
use App\Model\DriveType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;

final class BikeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('brand', TextType::class, [
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('model', TextType::class, [
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('category', EnumType::class, [
                'class' => Category::class,
                'choice_label' => fn (Category $c): string => $c->label(),
            ])
            ->add('driveType', EnumType::class, [
                'class' => DriveType::class,
                'choice_label' => fn (DriveType $d): string => $d->label(),
            ])
            ->add('battery', IntegerType::class, [
                'required' => false,
                'label' => 'Battery (Wh)',
                'constraints' => [new Assert\Positive()],
            ])
            ->add('frameSize', TextType::class, [
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('mileage', IntegerType::class, [
                'required' => false,
                'label' => 'Mileage (km)',
                'constraints' => [new Assert\PositiveOrZero()],
            ])
            ->add('originalPrice', MoneyType::class, [
                'currency' => 'EUR',
                'divisor' => 100, // user types euros, we store cents
                'constraints' => [new Assert\Positive()],
            ])
            ->add('currentPrice', MoneyType::class, [
                'currency' => 'EUR',
                'divisor' => 100,
                'constraints' => [new Assert\Positive()],
            ])
            ->add('condition', EnumType::class, [
                'class' => Condition::class,
                'choice_label' => fn (Condition $c): string => $c->label(),
            ]);

        // Cross-field rule: electric bikes must have a battery.
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $data = $event->getData();

            $driveType = $data['driveType'] ?? null;
            $battery = $data['battery'] ?? null;

            if ($driveType instanceof DriveType && $driveType->isElectric() && $battery === null) {
                $form->get('battery')->addError(
                    new FormError('Electric bikes need a battery capacity.'),
                );
            }
        });
    }
}