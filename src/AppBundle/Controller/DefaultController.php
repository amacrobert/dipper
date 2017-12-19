<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use AppBundle\Service\DipperCoreService;
use AppBundle\Service\GdaxService;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request, DipperCoreService $dipper, GdaxService $gdax) {

        $response = $gdax->getOrders(['status' => 'all', 'product_id' => 'LTC-USD']);
        //$response = $gdax->getOrder('28cd842e-d7fa-434c-8822-267fec710d72');

        return new JsonResponse($response);
    }
}
