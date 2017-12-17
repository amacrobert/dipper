<?php

namespace AppBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class TierAdmin extends AbstractAdmin {

    protected $datagridValues = [
        '_sort_by' => 'sequence',
        '_sort_order' => 'ASC',
    ];

    protected function configureFormFields(FormMapper $formMapper) {
        $formMapper
            ->add('sequence')
            ->add('spend', null, ['required' => true, 'help' => 'Order size (USD)'])
            ->add('spread', null, ['required' => true, 'help' => 'Difference from market value to buy/sell for (USD)'])
            ->add('lag_limit', null, ['required' => false, 'help' => 'Cancel and re-issue buy order when it trails market value by this much (USD)'])
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper) {
        $datagridMapper
            ->add('sequence')
        ;
    }

    protected function configureListFields(ListMapper $listMapper) {
        $listMapper
            ->add('sequence', null, ['editable' => true])
            ->add('spend', 'float', ['editable' => true])
            ->add('spread', 'decimal', ['editable' => true])
            ->add('lag_limit', 'decimal', ['editable' => true])
            ->add('_action', null, [
                    'actions' => [
                        'edit' => [],
                        'delete' => [],
                ]
            ])
        ;
    }
}
