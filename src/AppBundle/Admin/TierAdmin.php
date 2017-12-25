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
            ->with('General', ['class' => 'col-md-6'])
                ->add('sequence')
                ->add('active')
                ->add('spend', null, ['required' => true, 'help' => 'Buy size (USD)'])
                ->add('bid_spread', null, ['required' => true, 'help' => 'Bid for this amount below market ask (USD)'])
                ->add('ask_spread', null, ['required' => true, 'help' => 'Ask for this amount above associated buy price (USD)'])
            ->end()
            ->with('Conditions', ['class' => 'col-md-6'])
                ->add('lag_limit', null, ['required' => false, 'help' => 'Cancel and re-issue buy order when it trails market ask by this much (USD)'])
                ->add('buy_max_ppo', null, ['label' => 'Buy Max PPO', 'required' => false, 'help' => 'Only place buy order if the 26-minute percentage price oscillator is less than or equal to this amout (value in %)'])
                ->add('sell_min_ppo', null, ['label' => 'Sell Min PPO', 'required' => false, 'help' => 'Only place sell order if the 26-minute percentage price oscillator is greater than or equal to this amount (value in %)'])
            ->end()
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
            ->add('buy_max_ppo', null, ['label' => 'Buy Max PPO'])
            ->add('sell_min_ppo', null, ['label' => 'Sell Min PPO'])
            ->add('_action', null, [
                    'actions' => [
                        'edit' => [],
                        'delete' => [],
                ]
            ])
        ;
    }
}
