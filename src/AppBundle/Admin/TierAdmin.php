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

    protected $maxPerPage = 500;
    protected $perPageOptions = [10, 100, 200, 500, 1000];

    protected function configureFormFields(FormMapper $formMapper) {
        $formMapper
            ->add('sequence')
            ->add('active')
            ->add('spend', null, ['required' => true, 'help' => 'Buy size (USD)'])
            ->add('bid_spread', null, ['required' => true, 'help' => 'Bid for this amount below market ask (USD)'])
            ->add('ask_spread', null, ['required' => true, 'help' => 'Ask for this amount above associated buy price (USD)'])
            ->add('lag_limit', null, ['required' => false, 'help' => 'Cancel and re-issue buy order when it trails market ask by this much (USD)'])
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper) {
        $datagridMapper
            ->add('sequence')
            ->add('active')
        ;
    }

    protected function configureListFields(ListMapper $listMapper) {
        $listMapper
            ->add('sequence')
            ->add('active', null, ['editable' => true])
            ->add('spend')
            ->add('bid_spread')
            ->add('ask_spread')
            ->add('lag_limit')
            ->add('_action', null, [
                    'actions' => [
                        'edit' => [],
                        'delete' => [],
                ]
            ])
        ;
    }
}
