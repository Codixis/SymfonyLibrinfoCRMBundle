<?php

namespace Librinfo\CRMBundle\Admin;

use Sonata\CoreBundle\Validator\ErrorElement;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Librinfo\CoreBundle\Admin\Traits\HandlesRelationsAdmin;
use Librinfo\CRMBundle\Entity\Organism;
use Librinfo\CRMBundle\Entity\Contact;
use Librinfo\CRMBundle\Entity\Position;
use Librinfo\CRMBundle\Entity\ContactPhone;
use Librinfo\CRMBundle\Form\DataTransformer\CustomerCodeTransformer;
use Librinfo\CRMBundle\Form\DataTransformer\SupplierCodeTransformer;

class OrganismAdminConcrete extends OrganismAdmin
{
    use HandlesRelationsAdmin;

    public function getFormTheme()
    {
        return array_merge(
            parent::getFormTheme(),
            array('LibrinfoCRMBundle:Form:form_admin_fields.html.twig')
        );
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        // xxxxxxxAction in CRUD controller
        $collection->add('generateCustomerCode');
        $collection->add('generateSupplierCode');
        $collection->add('validateVat');
    }

    /**
     * @param FormMapper $mapper
     */
    protected function postConfigureFormFields(FormMapper $mapper)
    {
        $mapper->get('customerCode')->addViewTransformer(new CustomerCodeTransformer());
        $mapper->get('supplierCode')->addViewTransformer(new SupplierCodeTransformer());
    }

    public function preUpdate($object)
    {
        parent::preUpdate($object);

    }

    public function prePersist($object)
    {
        parent::prePersist($object);

        if ( $object->isIndividual() ) {
            // TODO: different rules for organism name creation in config (eg. "Firstname NAME" or "Name, Firstname"...)
            $firstname = mb_convert_case($this->getForm()->get('firstname')->getNormData(), MB_CASE_TITLE);
            $name = mb_strtoupper($this->getForm()->get('name')->getNormData());
            $object->setName($firstname . " " . $name);
        }
    }

    /**
     * @param Organism $organism
     */
    public function postPersist($organism)
    {
        parent::postPersist($organism);

        if ( $organism->isIndividual() ) {
            // Create a new Contact & Position associated to the organism
            $title = $this->getForm()->get('title')->getNormData();
            $firstname = $this->getForm()->get('firstname')->getNormData();
            $name = $this->getForm()->get('name')->getNormData();
            $contact = new Contact;
            $contact->setTitle($title);
            $contact->setFirstname($firstname);
            $contact->setName($name);
            $contact->setEmail($organism->getEmail());
            $contact->setAddress($organism->getAddress());
            $contact->setZip($organism->getZip());
            $contact->setCity($organism->getCity());
            $contact->setCountry($organism->getCountry());
            $this->getModelManager()->create($contact);

            foreach($organism->getPhones() as $oPhone) {
                $cPhone = new ContactPhone;
                $cPhone->setPhoneType($oPhone->getPhoneType());
                $cPhone->setNumber($oPhone->getNumber());
                $cPhone->setContact($contact);
                $this->getModelManager()->create($cPhone);
            }

            $position = new Position;
            $position->setOrganism($organism);
            $position->setContact($contact);
            $position->setEmail($organism->getEmail());
            $this->getModelManager()->create($position);
        }
    }

    /**
     * @param Organism $organism
     */
    public function preRemove($organism) {
        foreach($organism->getPositions() as $position) {
            $this->getModelManager()->delete($position);
        }
        foreach($organism->getPhones() as $phone) {
            $this->getModelManager()->delete($phone);
        }
        parent::preRemove($organism);
    }

    public function preBatchAction($actionName, \Sonata\AdminBundle\Datagrid\ProxyQueryInterface $query, array &$idx, $allElements) {
        parent::preBatchAction($actionName, $query, $idx, $allElements);
    }

    /**
     * @param ErrorElement $errorElement
     * @param mixed        $object
     *
     * @deprecated this feature cannot be stable, use a custom validator,
     *             the feature will be removed with Symfony 2.2
     */
    public function validate(ErrorElement $errorElement, $object)
    {
        $this->validateCustomerCode($errorElement, $object);
        $this->validateSupplierCode($errorElement, $object);

//        $errorElement
//            ->with('name')
//                ->assertLength(array('max' => 32))
//            ->end()
//        ;
    }

    /**
     * Customer code validator
     *
     * @param ErrorElement $errorElement
     * @param Organism $object
     */
    public function validateCustomerCode(ErrorElement $errorElement, $object) {
        $is_new = empty($object->getId());
        $code = $object->getCustomerCode();

        if ( empty($code) && $object->isCustomer() ) {
            $errorElement
                ->with('customerCode')
                    ->addViolation('A customer code is required for customers')
                ->end()
            ;
        }

        $regexp = sprintf('/^%s(\d{%d})$/', Organism::CC_PREFIX, Organism::CC_LENGTH);
        if ( !empty($code) && !preg_match($regexp, $code) ) {
            $msg = 'Wrong format for supplier code. It shoud be: ';
            $msg .= Organism::CC_PREFIX ? '%prefix% + %length% digits' : '%length% digits';
            $params = ['%prefix%' => Organism::CC_PREFIX, '%length%' => Organism::CC_LENGTH];
            $errorElement
                ->with('customerCode')
                    ->addViolation($msg, $params)
                ->end()
            ;
        }

        if ( !empty($code) ) {
            $valid = true;
            $organisms = $this->getModelManager()->findBy(
                 'Librinfo\CRMBundle\Entity\Organism',
                 ['customerCode' => $code]
             );
            if ( $organisms ) {
                if ( $is_new )
                    $valid = false;
                else foreach ( $organisms as $organism ) if ( $organism->getId() != $object->getId() ) {
                    $valid = false;
                    break;
                }
            }
            if ( !$valid )
                $errorElement
                    ->with('customerCode')
                        ->addViolation('This customer code is already in use')
                    ->end()
                ;
        }

    }


    /**
     * Supplier code validator
     *
     * @param ErrorElement $errorElement
     * @param Organism $object
     */
    public function validateSupplierCode(ErrorElement $errorElement, $object) {
        $is_new = empty($object->getId());
        $code = $object->getSupplierCode();

        if ( empty($code) && $object->isSupplier() ) {
            $errorElement
                ->with('supplierCode')
                    ->addViolation('A supplier code is required for suppliers')
                ->end()
            ;
        }

        $regexp = sprintf('/^%s(\d{%d})$/', Organism::SC_PREFIX, Organism::SC_LENGTH);
        if ( !empty($code) && !preg_match($regexp, $code) ) {
            $msg = 'Wrong format for supplier code. It shoud be: ';
            $msg .= Organism::SC_PREFIX ? '%prefix% + %length% digits' : '%length% digits';
            $params = ['%prefix%' => Organism::SC_PREFIX, '%length%' => Organism::SC_LENGTH];
            $errorElement
                ->with('supplierCode')
                    ->addViolation($msg, $params)
                ->end()
            ;
        }

        if ( !empty($code) ) {
            $valid = true;
            $organisms = $this->getModelManager()->findBy(
                 'Librinfo\CRMBundle\Entity\Organism',
                 ['supplierCode' => $code]
             );
            if ( $organisms ) {
                if ( $is_new )
                    $valid = false;
                else foreach ( $organisms as $organism ) if ( $organism->getId() != $object->getId() ) {
                    $valid = false;
                    break;
                }
            }
            if ( !$valid )
                $errorElement
                    ->with('supplierCode')
                        ->addViolation('This supplier code is already in use')
                    ->end()
                ;
        }
    }
}
