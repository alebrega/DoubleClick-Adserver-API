<?php

class DfpController extends BaseController {

    public $restful = true;

    public function createAdUnit() {
        try {
            $data = Input::json('data');
            $credentials = Input::json('credentials');
            Verification::isExist($data, 'adunit', TRUE);
		/*
$credentials = array(
              'oauth2info' => array('client_id' => "56018185674-7d8uc8mc9bjo1j1hb5lrpte9qptn6jj3.apps.googleusercontent.com",
              'client_secret' => "OeYlLmOFyIfp9KaxGaJhm20w",
              'refresh_token' => '1/DU2ZnIb3oKBpThisOt15Q3-_jTRdrESg8NJtyrPVFRk'),
              'code' => '25379366', 'name' => 'AdtomatikForAdmin'
              );

              	$data['adunit']['name'] = "ADT_Publisher_fsd55f.com_4240_ee".rand(888,999999);
		$data['adunit']['description'] = "Adtomatik Publisher";
		$data['adunit']['targetWindow'] = "BLANK";*/

            $dfp = new Dfp();
            $res = $dfp->newAdUnit($credentials, $data['adunit']);

            return Response::json(array(
                        'error' => !$res['type'],
                        'message' => $res['data']), 200
            );
        } catch (Exception $ex) {
            return Response::json(array(
                        'error' => true,
                        'message' => $ex->getMessage()), $ex->getCode()
            );
        }
    }

    public function createLineItem() {

        try {
            $data = Input::json('data');
            $credentials = Input::json('credentials');
            Verification::isExist($data, 'lineitem', TRUE);

            $dfp = new Dfp();
            $res = $dfp->newLineItem($credentials, $data['lineitem']);

            return Response::json(array(
                        'error' => !$res['type'],
                        'message' => $res['data']), 200
            );
        } catch (Exception $ex) {
            return Response::json(array(
                        'error' => true,
                        'message' => $ex->getMessage()), $ex->getCode()
            );
        }
    }

    public function createCampaign() {

        try {

            $data = Input::json('data');
            $credentials = Input::json('credentials');
            Verification::isExist($data, 'campaign', TRUE);

            /*
              $credentials = array(
              'oauth2info' => array('client_id' => "56018185674-7d8uc8mc9bjo1j1hb5lrpte9qptn6jj3.apps.googleusercontent.com",
              'client_secret' => "OeYlLmOFyIfp9KaxGaJhm20w",
              'refresh_token' => '1/DU2ZnIb3oKBpThisOt15Q3-_jTRdrESg8NJtyrPVFRk'),
              'code' => '25379366', 'name' => 'AdtomatikForAdmin'
              );
              $data['campaign']['id'] = 4401;*/
             
            $dfp = new Dfp();
            $res = $dfp->newCampaign($credentials, $data['campaign']);

            return Response::json(array(
                        'error' => !$res['type'],
                        'message' => $res['data']), 200
            );
        } catch (Exception $ex) {
            return Response::json(array(
                        'error' => true,
                        'message' => $ex->getMessage()), $ex->getCode()
            );
        }
    }

    public function createAdvertiser() {
        try {
            /* $data = Input::json('data');
              $credentials = Input::json('credentials');
              Verification::isExist($data, 'campaign', TRUE);
             */
            $credentials = array(
                'oauth2info' => array('client_id' => "56018185674-7d8uc8mc9bjo1j1hb5lrpte9qptn6jj3.apps.googleusercontent.com",
                    'client_secret' => "OeYlLmOFyIfp9KaxGaJhm20w",
                    'refresh_token' => '1/DU2ZnIb3oKBpThisOt15Q3-_jTRdrESg8NJtyrPVFRk'),
                'code' => '25379366', 'name' => 'AdtomatikForAdmin'
            );

            $data['advertiser']['name'] = "Anunciante de prueba 2015";
            $data['advertiser']['id'] = 1952;

            $dfp = new Dfp();
            $res = $dfp->newAdvertiser($credentials, $data['advertiser']);

            return Response::json(array(
                        'error' => !$res['type'],
                        'message' => $res['data']), 200
            );
        } catch (Exception $ex) {
            return Response::json(array(
                        'error' => true,
                        'message' => $ex->getMessage()), $ex->getCode()
            );
        }
    }

    public function updateLineItem() {
        try {
            $data = Input::json('data');
            $credentials = Input::json('credentials');
            Verification::isExist($data, 'lineitem', TRUE);

            $dfp = new Dfp();
            $res = $dfp->updateLineItem($credentials, $data['lineitem']);

            return Response::json(array(
                        'error' => !$res['type'],
                        'message' => $res['data']), 200
            );
        } catch (Exception $ex) {
            return Response::json(array(
                        'error' => true,
                        'message' => $ex->getMessage()), $ex->getCode()
            );
        }
    }

    public function getOrders() {
        try {

            $data = Input::json('data');
            $credentials = Input::json('credentials');

            $dfp = new Dfp();
            if ($data) {
                if (Verification::isExist($data, 'filters', TRUE))
                    $res = $dfp->getOrders($credentials, $data['filters']);
            } else {
                $res = $dfp->getOrders($credentials, []);
            }

            return Response::json(array(
                        'error' => !$res['type'],
                        'message' => $res['data']), 200
            );
        } catch (Exception $ex) {
            return Response::json(array(
                        'error' => true,
                        'message' => $ex->getMessage()), $ex->getCode()
            );
        }
    }

    public function getLineItems() {
        try {

            $data = Input::json('data');
            $credentials = Input::json('credentials');
            /*
              $credentials = array(
              'oauth2info' => array('client_id' => "56018185674-7d8uc8mc9bjo1j1hb5lrpte9qptn6jj3.apps.googleusercontent.com",
              'client_secret' => "OeYlLmOFyIfp9KaxGaJhm20w",
              'refresh_token' => '1/DU2ZnIb3oKBpThisOt15Q3-_jTRdrESg8NJtyrPVFRk'),
              'code' => '25379366', 'name' => 'AdtomatikForAdmin'
              );

              $data['lineItem']['id'] = 161983486;
             */

            $dfp = new Dfp();
            if ($data) {
                if (Verification::isExist($data, 'filters', TRUE))
                    $res = $dfp->getLineItems($credentials, $data['lineItem']);
            } else {
                $res = $dfp->getLineItems($credentials, []);
            }

            return Response::json(array(
                        'error' => !$res['type'],
                        'message' => $res['data']), 200
            );
        } catch (Exception $ex) {
            return Response::json(array(
                        'error' => true,
                        'message' => $ex->getMessage()), $ex->getCode()
            );
        }
    }

    public function excludeAdunitsFromLineItem() {
        try {
            $data = Input::json('data');
            $credentials = Input::json('credentials');
            Verification::isExist($data, 'lineitem', TRUE);

            $dfp = new Dfp();
            $res = $dfp->excludeAdunitsFromLineItem($credentials, $data['lineitem']);

            return Response::json(array(
                        'error' => !$res['type'],
                        'message' => $res['data']), 200
            );
        } catch (Exception $ex) {
            return Response::json(array(
                        'error' => true,
                        'message' => $ex->getMessage()), $ex->getCode()
            );
        }
    }

    public function getToken() {
        try {
            $data = Input::json('data');
            $credentials = Input::json('credentials');
            /*
              return Response::json(array(
              'error' => FALSE,
              'message' => $data,
              'credenciales' => $credentials), 200
              );
             */


            //Verification::isExist($data, 'code', TRUE);



            $dfp = new Dfp();
            $res = $dfp->getToken($credentials, $data);
            //$res = $dfp->refreshToken($credentials);
            return Response::json(array(
                        'error' => FALSE,
                        'message' => $res), 200
            );
        } catch (Exception $ex) {
            return Response::json(array(
                        'error' => true,
                        'message' => $ex->getMessage()), $ex->getCode()
            );
        }
    }

    public function getOauth2Code() {
        try {
            $data = Input::json('credentials');

            $dfp = new Dfp();
            $res = $dfp->refreshToken($data);

            return Response::json(array(
                        'error' => FALSE,
                        'message' => $res), 200
            );
        } catch (Exception $ex) {
            return Response::json(array(
                        'error' => true,
                        'message' => $ex->getMessage()), $ex->getCode()
            );
        }
    }

    public function getUserNetwork() {
        try {
            $data = Input::json('credentials');

            $dfp = new Dfp();
            $networks = $dfp->getUserNetwork($data);
            $res = NULL;
            foreach ($networks as $network) {
                if (!$network->isTest) {
                    $res = $network;
                    break;
                }
            }

            return Response::json(array(
                        'error' => FALSE,
                        'message' => $res), 200
            );
        } catch (Exception $ex) {
            return Response::json(array(
                        'error' => true,
                        'message' => $ex->getMessage()), $ex->getCode()
            );
        }
    }

    public function createPlacement() {
        try {

            $data = Input::json('data');
            $credentials = Input::json('credentials');
            Verification::isExist($data, 'placement', TRUE);
            /*
              $credentials = array(
              'oauth2info' => array('client_id' => "56018185674-7d8uc8mc9bjo1j1hb5lrpte9qptn6jj3.apps.googleusercontent.com",
              'client_secret' => "OeYlLmOFyIfp9KaxGaJhm20w",
              'refresh_token' => '1/DU2ZnIb3oKBpThisOt15Q3-_jTRdrESg8NJtyrPVFRk'),
              'code' => '25379366', 'name' => 'AdtomatikForAdmin'
              ); */
            //$categorias[] = array('name' => 'Replicas', 'adunitId' => 44916646);
            /*
              $categorias[] = array('name' => 'Women', 'adunitId' => );
              $categorias[] = array('name' => 'SaludyFitness', 'adunitId' => 44556046);
              $categorias[] = array('name' => 'RedesSociales ', 'adunitId' => 44556166);
              $categorias[] = array('name' => 'ModayShopping', 'adunitId' => 44556286);
              $categorias[] = array('name' => 'Mascotas', 'adunitId' => 44556406);
              $categorias[] = array('name' => 'MamayBebe', 'adunitId' => 44556526);
              $categorias[] = array('name' => 'Juegos', 'adunitId' => 44556646);
              $categorias[] = array('name' => 'HogaryJardin ', 'adunitId' => 44556766);
              $categorias[] = array('name' => 'Glam ', 'adunitId' => 44556886);44555926
              $categorias[] = array('name' => 'Espectaculos ', 'adunitId' => 44557006);
              $categorias[] = array('name' => 'Entretenimiento ', 'adunitId' => 44557126);
              $categorias[] = array('name' => 'EjecutivasyProfesionales ', 'adunitId' => 44557246);
              $categorias[] = array('name' => 'Educacion ', 'adunitId' => 44557366);
              $categorias[] = array('name' => 'Cocina', 'adunitId' => 44557486);
              $categorias[] = array('name' => 'BodasyFestejos ', 'adunitId' => 44557606);
              $categorias[] = array('name' => 'BellezayCosmetica ', 'adunitId' => 44557726);
              $categorias[] = array('name' => 'AmoryPareja ', 'adunitId' => 44557846);
             */

            $dfp = new Dfp();
            $res = $dfp->newPlacement($credentials, $data['placement']);

            return Response::json(array(
                        'error' => FALSE,
                        'message' => $res), 200
            );
        } catch (Exception $ex) {
            return Response::json(array(
                        'error' => true,
                        'message' => $ex->getMessage()), $ex->getCode()
            );
        }
    }

    public function addAdUnitsToPlacement() {
        try {
            $data = Input::json('data');
            $credentials = Input::json('credentials');
            Verification::isExist($data, 'placement', TRUE);

            $dfp = new Dfp();
            $res = $dfp->addAdUnitsToPlacement($credentials, $data['placement']);

            return Response::json(array(
                        'error' => FALSE,
                        'message' => $res), 200
            );
        } catch (Exception $ex) {
            return Response::json(array(
                        'error' => true,
                        'message' => $ex->getMessage()), $ex->getCode()
            );
        }
    }

    public function getReport() {
      
        try {
            
            $data = Input::json('data');
            $credentials = Input::json('credentials');
            Verification::isExist($data, 'report', TRUE);
            
            $dfp = new Dfp();
            
            $res = $dfp->getReport($credentials, $data['report']);
          
            return Response::json(array(
                        'error' => FALSE,
                        'message' => $res), 200
            );
        } catch (Exception $ex) {
            return Response::json(array(
                        'error' => true,
                        'message' => $ex->getMessage()), $ex->getCode()
            );
        }
    }

    public function getAdUnits() {
        try {

            //$data = Input::json('data');
            $credentials = Input::json('credentials');

            $dfp = new Dfp();
            $res = $dfp->getAdUnits($credentials);

            return Response::json(array(
                        'error' => FALSE,
                        'message' => $res), 200
            );
        } catch (Exception $ex) {
            return Response::json(array(
                        'error' => TRUE,
                        'message' => $ex->getMessage()), $ex->getCode()
            );
        }
    }

}
