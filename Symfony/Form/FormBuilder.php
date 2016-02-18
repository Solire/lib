<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Solire\Lib\Symfony\Form;

use Solire\Lib\Doctrine\Orm;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Forms;

/**
 * Description of FormBuilder.
 *
 * @author thansen
 */
class FormBuilder
{
    /**
     * Constructeur de formulaire.
     *
     * @var FormFactory
     */
    private $formFactoryBuilder;

    public function __construct(Orm $orm)
    {
        $this->formFactoryBuilder = Forms::createFormFactoryBuilder()
            ->addExtension(
                new DoctrineOrmExtension(
                    new ManagerRegistry($orm->getEM(), $orm->getConnection())
                )
            )
        ;
    }

    public function getFormFactory()
    {
        return $this->formFactoryBuilder->getFormFactory();
    }
}
