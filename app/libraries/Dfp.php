<?php

require_once 'Google/Api/Ads/Dfp/Lib/DfpUser.php';
require_once 'Google/Api/Ads/Dfp/v201605/InventoryService.php';
require_once 'Google/Api/Ads/Dfp/Util/v201605/ReportDownloader.php';
require_once 'Google/Api/Ads/Dfp/Util/v201605/StatementBuilder.php';
require_once 'Google/Api/Ads/Dfp/Util/v201605/DateTimeUtils.php';

class Dfp {

    const VERSION = 'v201605'; //v201502

    public $production = array(
        'oauth2info' => array('client_id' => "56018185674-7d8uc8mc9bjo1j1hb5lrpte9qptn6jj3.apps.googleusercontent.com",
            'client_secret' => "OeYlLmOFyIfp9KaxGaJhm20w",
            'refresh_token' => '1/DU2ZnIb3oKBpThisOt15Q3-_jTRdrESg8NJtyrPVFRk'),
        'code' => '25379366',
        'name' => 'AdtomatikForAdmin'
    );
    public $test = array(
        'oauth2info' => array('client_id' => "765956154715-uhil3e6tcpm4a8ourc2ij9da1g7hnbl9.apps.googleusercontent.com",
            'client_secret' => "4t_T1GV_D9ebvbB30soGfqrY",
            'refresh_token' => "1/qlvmbuR8lL-FNujk3yJqM88xOkxsQ7MOX6ElEH3u04QMEudVrK5jSpoR30zcRFq6"),
        'code' => '40590846',
        'name' => 'adtomatik'
    );

    public function refreshToken($credentials) {
        $user = $this->_dfpUser($credentials);
        return $this->_getOAuth2Credential($user);
    }

    public function getUserNetwork($credentials) {
        $user = $this->_dfpUser($credentials);
        return $this->_getAllNetwork($user);
    }

    public function getOrders($credentials, $data) {
        try {
            $filters = [];
            $user = $this->_dfpUser($credentials);
            $orderService = $this->_service($user, 'Order');

            if (Verification::isExist($data, 'advertiserId')) {
                $filters['advertiserId'] = "advertiserId IN ('" . implode("', '", $data['advertiserId']) . "')";
            }
            if (Verification::isExist($data, 'id')) {
                $filters['id'] = "id IN ('" . implode("', '", $data['id']) . "')";
            }
            if (Verification::isExist($data, 'name')) {
                $filters['name'] = "name IN ('" . implode("', '", $data['name']) . "')";
            }
            if (Verification::isExist($data, 'salespersonId')) {
                $filters['salespersonId'] = "salespersonId IN ('" . implode("', '", $data['salespersonId']) . "')";
            }
            if (Verification::isExist($data, 'status')) {
                $filters['status'] = "status IN ('" . implode("', '", $data['status']) . "')";
            }
            if (Verification::isExist($data, 'traffickerId')) {
                $filters['traffickerId'] = "traffickerId IN ('" . implode("', '", $data['traffickerId']) . "')";
            }

            $statementBuilder = new StatementBuilder();
            if (count($filters) > 0) {
                $statementBuilder->Where(implode(' AND ', $filters))
                        ->OrderBy('id DESC')
                        ->Limit(StatementBuilder::SUGGESTED_PAGE_LIMIT);
            } else {
                $statementBuilder->OrderBy('id DESC')
                        ->Limit(StatementBuilder::SUGGESTED_PAGE_LIMIT);
            }
            return ['type' => true, 'data' => $orderService->getOrdersByStatement(
                        $statementBuilder->ToStatement())];
        } catch (Exception $ex) {
            return ['type' => false, 'data' => $ex];
        }
    }

    public function excludeAdunitsFromLineItem($credentials, $data) {
        try {
            $user = $this->_dfpUser($credentials);
            $lineItemService = $this->_service($user, 'LineItem');
            $lineItems = $this->getLineItem($data['id'], $credentials);
            $targetedAdunits = $lineItems->results[0]->targeting->inventoryTargeting->targetedAdUnits;
            if (!isset($lineItems->results[0])) {
                return ['type' => false, 'data' => 'LINEITEM_NOT_FOUND'];
            } else {
                if (!isset($lineItems->results[0]->id)) {
                    return ['type' => false, 'data' => 'LINEITEM_NOT_FOUND'];
                }
            }
            $targetedAdunitsArray = array();
            foreach ($targetedAdunits as $adUnitId) {
                $founded = in_array($adUnitId->adUnitId, $data['adunits']);
                if (!$founded)
                    $targetedAdunitsArray[] = $adUnitId->adUnitId;
            }
            //TARGETING
            $targetIds = array();
            foreach ($targetedAdunitsArray as $id_adunit) {
                $adunit = new AdUnitTargeting();
                $adunit->adUnitId = $id_adunit;
                $targetIds[] = $adunit;
            }

            $lineItems->results[0]->targeting->inventoryTargeting->targetedAdUnits = $targetIds;
            $res = $lineItemService->updateLineItems([$lineItems->results[0]]);
            if (!isset($res->results[0])) {
                return ['type' => false, 'data' => 'LINEITEM_NOT_UPDATED'];
            } else {
                if (!isset($res->results[0]->id)) {
                    return ['type' => false, 'data' => 'LINEITEM_NOT_UPDATED'];
                }
            }
            return ['type' => true, 'data' => $res];
        } catch (Exception $ex) {
            return ['type' => false, 'data' => $ex];
        }
    }

    public function getLineItem($id, $credentials) {
        $user = $this->_dfpUser($credentials);
        $lineItemService = $this->_service($user, 'LineItem');
        $statementBuilder = new StatementBuilder();
        $statementBuilder->Where('id = ' . $id)
                ->OrderBy('id DESC')
                ->Limit(1);
        return $lineItemService->getLineItemsByStatement(
                        $statementBuilder->ToStatement());
    }

    public function getLineItems($credentials, $lineItem) {
        $user = $this->_dfpUser($credentials);
        $lineItemService = $this->_service($user, 'LineItem');
        $statementBuilder = new StatementBuilder();
        $statementBuilder->Where('id = ' . $lineItem['id'])
                ->OrderBy('id DESC')
                ->Limit(1);
        return ['type' => true, 'data' => $lineItemService->getLineItemsByStatement(
                    $statementBuilder->ToStatement())];
    }

    public function getAdUnits($credentials) {
        try {
            $user = $this->_dfpUser($credentials);
            $inventoryService = $this->_service($user, 'Inventory');
            //$inventoryService = $this->_service($user, 'Label');
            $statementBuilder = new StatementBuilder();
            $statementBuilder/*->Where('id = 6459752')*/->OrderBy('id ASC')
                    ->Limit(StatementBuilder::SUGGESTED_PAGE_LIMIT);
            return ['type' => true, 'data' => $inventoryService->GetAdUnitsByStatement(
                        $statementBuilder->ToStatement())];
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function addAdUnitsToPlacement($credentials, $placement) {
        try {
            $user = $this->_dfpUser($credentials);

//Verify mandatory fields
            Verification::isExist($placement, 'adunits', TRUE);
            Verification::isExist($placement, 'id', TRUE);

            $inventoryService = $this->_service($user, 'Inventory');
            $statementBuilder = new StatementBuilder();
            $statementBuilder->Where("id in (" . $placement['adunits'] . ")");
// Create action.
            $action = new AssignAdUnitsToPlacement();
            $action->placementId = $placement['id'];

// Perform action.
            return $inventoryService->performAdUnitAction($action, $statementBuilder->ToStatement());
        } catch (Exception $ex) {
            return $ex;
        }
    }

    public function newAdUnit($credentials, $newAdUnit) {
        try {
            $user = $this->_dfpUser($credentials);
            $user->LogDefaults();
            //Verify mandatory fields
            Verification::isExist($newAdUnit, 'name', TRUE);
            $inventoryService = $this->_service($user, 'Inventory');
            // Get the effective root ad unit's ID for all ad units to be created under.
            $network = $this->_getNetwork($user);
            $effectiveRootAdUnitId = $network->effectiveRootAdUnitId;

            $adUnit = new AdUnit();
            $adUnit->parentId = $effectiveRootAdUnitId;
            $adUnit->name = $newAdUnit['name'];



            //if (Verification::isExist($newAdUnit, 'adUnitCode'))
            //$adUnit->adUnitCode = $newAdUnit['adUnitCode'];
            if (Verification::isExist($newAdUnit, 'description'))
                $adUnit->description = $newAdUnit['description'];
            if (Verification::isExist($newAdUnit, 'targetWindow'))
                $adUnit->targetWindow = $newAdUnit['targetWindow'];

            if (Verification::isExist($newAdUnit, 'size')) {
                //Verify mandatory fields
                Verification::isExist($newAdUnit['size'], 'width', TRUE);
                Verification::isExist($newAdUnit['size'], 'height', TRUE);
                $adUnitSize = new AdUnitSize();
                if (Verification::isExist($newAdUnit['size'], 'environmentType'))
                    $adUnitSize->environmentType = $newAdUnit['size']['environmentType'];
                $adUnitSize->size = new Size();
                $adUnitSize->size->width = $newAdUnit['size']['width'];
                $adUnitSize->size->height = $newAdUnit['size']['height'];
                if (Verification::isExist($newAdUnit['size'], 'isAspectRatio'))
                    $adUnitSize->size->isAspectRatio = $newAdUnit['size']['isAspectRatio'];
                else
                    $adUnitSize->size->isAspectRatio = FALSE;

                $adUnitSizes[] = $adUnitSize;

                $adUnit->adUnitSizes = $adUnitSizes;
            }
            $adUnits[] = $adUnit;
            // Create the ad units on the server.
            $adUnits = $inventoryService->createAdUnits($adUnits);


            // Display results.
            if (isset($adUnits)) {
                foreach ($adUnits as $adUnit) {

                    return ['type' => true, 'data' => $adUnit];
                }
            } else {
                throw new Exception("No ad units created.");
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
            //return ['type' => false, 'data' => $ex];
        }
    }

    public function newPlacement($credentials, $newPlacement) {
        try {
            $user = $this->_dfpUser($credentials);
            //$user->LogDefaults();
            //Verify mandatory fields
            Verification::isExist($newPlacement, 'name', TRUE);
            Verification::isExist($newPlacement, 'defaultAdunit', TRUE);

            $placementService = $this->_service($user, 'Placement');

            $placement = new Placement();

            $placement->name = $newPlacement['name'];
            $placement->targetedAdUnitIds[] = $newPlacement['defaultAdunit'];

            if (Verification::isExist($newPlacement, 'isAdSenseTargetingEnabled'))
                $placement->isAdSenseTargetingEnabled = $newPlacement['isAdSenseTargetingEnabled'];
            else
                $placement->isAdSenseTargetingEnabled = FALSE;

            $placements[] = $placement;

            $placements = $placementService->createPlacements($placements);

            // Display results.
            if (isset($placements)) {
                foreach ($placements as $placement) {
                    return $placement->id;
                }
            } else {
                throw new Exception("No placements created.");
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function newLineItem($credentials, $newLineItem) {
        try {

            $user = $this->_dfpUser($credentials);
            //$user->LogDefaults();
            $lineItemService = $this->_service($user, 'LineItem');

            $lineItem = new LineItem();
            $lineItem->name = $newLineItem['name'];
            $lineItem->orderId = $newLineItem['order_id'];
            $lineItem->lineItemType = 'AD_EXCHANGE';
            $lineItem->webPropertyCode = 'ca-video-pub-6966735019331824';
            $inventoryTargeting = new InventoryTargeting();

            $adUnitTargeting = new AdUnitTargeting();
            $adUnitTargeting->adUnitId = $newLineItem['adunit_id'];

            $inventoryTargeting->targetedAdUnits = array($adUnitTargeting);
            $inventoryTargeting->includeDescendants = true;

            $targeting = new Targeting();
            $targeting->inventoryTargeting = $inventoryTargeting;
            $lineItem->targeting = $targeting;

            $creativePlaceholder = new CreativePlaceholder();
            $creativePlaceholder->size = new Size(640, 480, false);
            $lineItem->creativePlaceholders = array($creativePlaceholder);
            $lineItem->startDateTimeType = 'IMMEDIATELY';
            $lineItem->unlimitedEndDateTime = true;
            $lineItem->creativeRotationType = 'EVEN';
            $lineItem->environmentType = 'VIDEO_PLAYER';

            $lineItem->costType = 'CPM';
            $lineItem->costPerUnit = new Money('USD', 0);

            $goal = new Goal();
            $goal->units = -1;
            $goal->unitType = 'IMPRESSIONS';
            $goal->goalType = 'NONE';
            $lineItem->primaryGoal = $goal;

            $lineItems[] = $lineItem;

            $lineItems = $lineItemService->createLineItems($lineItems);

            // Display results.
            if (isset($lineItems)) {
                return ['type' => true, 'data' => $lineItems[0]];
            } else {
                throw new Exception("No line items created.");
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function newAdvertiser($credentials, $newAdvertiser) {
        try {
            $user = $this->_dfpUser($credentials);
            $companyService = $this->_service($user, 'Company');

            $company = new Company();
            $company->name = $newAdvertiser['name'] . ' - ' . $newAdvertiser['id'];
            $company->type = 'ADVERTISER';
            $company->creditStatus = 'ACTIVE';

            $companies[] = $company;

            $res = $companyService->createCompanies($companies);

            return $res;
        } catch (Exception $ex) {

            throw new Exception($ex->getMessage());
        }
    }

    public function getAllPlacements($credentials) {
        try {
            $user = $this->_dfpUser($credentials);
            $placementService = $this->_service($user, 'Placement');

            // Create a statement to select only active placements.
            $statementBuilder = new StatementBuilder();
            $statementBuilder->Where('status = :status')
                    ->OrderBy('id ASC')
                    ->Limit(StatementBuilder::SUGGESTED_PAGE_LIMIT)
                    ->WithBindVariableValue('status', 'ACTIVE');

            // Default for total result set size.
            $totalResultSetSize = 0;

            do {
                // Get placements by statement.
                $page = $placementService->getPlacementsByStatement(
                        $statementBuilder->ToStatement());

                // Display results.
                if (isset($page->results)) {
                    $totalResultSetSize = $page->totalResultSetSize;
                    $i = $page->startIndex;
                    foreach ($page->results as $placement) {
                        //$placements[] = array('id' => $placement->id, 'name' => $placement->name);
                        $placements[] = $placement->id;
                    }
                }

                $statementBuilder->IncreaseOffsetBy(StatementBuilder::SUGGESTED_PAGE_LIMIT);
            } while ($statementBuilder->GetOffset() < $totalResultSetSize);

            return $placements;
        } catch (Exception $ex) {

            throw new Exception($ex->getMessage());
        }
    }

    public function removeAccents($stripAccents) {
        return preg_replace('/[^a-z0-9\s]/i', '', $stripAccents);
    }

    public function newCampaign($credentials, $newCampaign) {

        $anunciantes = new Anunciantes();

        $paises = $anunciantes->get_campaign_countries($newCampaign['id']);
        $formatos = $anunciantes->get_campaign_sizes($newCampaign['id']);

        try {
            // preparo los datos para la creacion
            $campania = $anunciantes->get_campaign($newCampaign['id']);

            $id_anunciante_dfp = $campania['id_anunciante_dfp'];

            //Si no existe el anunciante en DFP , lo crea
            if (!$campania['id_anunciante_dfp']) {

                $newAdvertiser['id'] = $campania['id_anunciante_mf'];
                $newAdvertiser['name'] = $this->removeAccents($campania['nombre_anunciante']);

                $adv = $this->newAdvertiser($credentials, $newAdvertiser);

                $id_anunciante_dfp = $adv[0]->id;

                $data_advertiser['id_dfp'] = $id_anunciante_dfp;
                $data_advertiser['id_mf'] = $campania['id_anunciante_mf'];

                //Actualizar base dfp
                $anunciantes->update_adververtiser($data_advertiser);
            }

            if (!$campania)
                return FALSE;

            $user = $this->_dfpUser($credentials);
            //$user->LogDefaults();
            $orderService = $this->_service($user, 'Order');

            $order = new Order();

            $campaign_name = $this->removeAccents($campania['nombre']);

            //$order->name = 'TEST ' . $campaign_name . '_' . $campania['id'] . '-' . rand(100, 999);
            $order->name = $campaign_name . '_' . $campania['id'];
            $order->advertiserId = $id_anunciante_dfp;
            $order->traffickerId = 117491206;
            $order->status = 'PENDING_APPROVAL';

            $orders[] = $order;

            $res_order = $orderService->createOrders($orders);

            $lineItemService = $this->_service($user, 'LineItem');

            //$creativePlaceholders
            foreach ($formatos as $row) {
                if ($row['id'] == 10 || $row['id'] == 11) {
                    $sizeType = 'INTERSTITIAL';
                } else {
                    $sizeType = 'PIXEL';
                }
                $width = $row['width'];
                $height = $row['height'];

                $creativePlaceholder = new CreativePlaceholder();
                $creativePlaceholder->size = new Size($width, $height, FALSE);
                $creativePlaceholder->creativeSizeType = $sizeType;
                $creativePlaceholders[] = $creativePlaceholder;
            }

            $fecha = explode(' ', $campania['fecha_inicio']);

            if ($fecha[0] == date('Y-m-d')) {
                $hora = date('H');
                $minuto = date('i');
                $segundos = '00';

                $fecha_inicio = date('Y-m-d') . ' ' . $hora . ':' . $minuto . ':' . $segundos;
            } else {
                $fecha_inicio = $campania['fecha_inicio'];
            }

            $lineItems = null;

            if ($campania['segmentacion_id'] == 1) { // toda la red
                $targetedPlacements = null;
                $inventoryTargeting = null;
                $lineItem = null;
                $targeting = null;
                $countryLocations = null;

                /* TARGETING GENERAL */
                $targeting = new Targeting();
                //GEO
                $geoTargeting = new GeoTargeting();
                for ($i = 0; $i < sizeof($paises); $i++) {
                    $countryLocation = new DfpLocation();
                    $countryLocation->id = $paises[$i];

                    $countryLocations[] = $countryLocation;
                }

                $geoTargeting->targetedLocations = $countryLocations;
                $targeting->geoTargeting = $geoTargeting;

                //TECHNOLOGY
                if ($campania['tipo_campania'] == 'impresiones_mobile') {
                    $technologyTargeting = new TechnologyTargeting();
                    $deviceCategoryTargeting = new DeviceCategoryTargeting();

                    $deviceCategoryTechnology = new Technology();
                    $deviceCategoryTechnology->id = 30000;

                    $deviceCategoryTargeting->excludedDeviceCategories = array($deviceCategoryTechnology);
                    $technologyTargeting->deviceCategoryTargeting = $deviceCategoryTargeting;
                    $targeting->technologyTargeting = $technologyTargeting;
                }
                /* FIN TARGETING GENERAL */

                //INVENTORY
                $inventoryTargeting = new InventoryTargeting();
                $targetedPlacements = $this->getAllPlacements($credentials);
                $inventoryTargeting->targetedPlacementIds = $targetedPlacements;
                $targeting->inventoryTargeting = $inventoryTargeting;

                $lineItem = new LineItem();

                $lineItem->orderId = $res_order[0]->id;
                $lineItem->name = 'Toda la red';
                $lineItem->disableSameAdvertiserCompetitiveExclusion = TRUE;
                $lineItem->creativePlaceholders = $creativePlaceholders;

                if ($campania['tipo_campania'] == 'pre_roll' || $campania['tipo_campania'] == 'overlay') {
                    $lineItem->environmentType = 'VIDEO_PLAYER';
                }

                if ($campania['tipo_campania'] == 'data') {
                    $lineItem->lineItemType = 'SPONSORSHIP';
                    $lineItem->startDateTime = DateTimeUtils::GetDfpDateTime(new DateTime($fecha_inicio));
                    $lineItem->endDateTime = DateTimeUtils::GetDfpDateTime(new DateTime($campania['fecha_fin']));

                    $lineItem->deliveryRateType = 'AS_FAST_AS_POSSIBLE';
                    $lineItem->creativeRotationType = 'OPTIMIZED';
                } else {
                    if ($campania['type_DFP'] == 'PRICE_PRIORITY') {
                        $lineItem->lineItemType = 'PRICE_PRIORITY';

                        $lineItem->startDateTime = DateTimeUtils::GetDfpDateTime(new DateTime($fecha_inicio));
                        $lineItem->endDateTime = DateTimeUtils::GetDfpDateTime(new DateTime($campania['fecha_fin']));

                        $lineItem->deliveryRateType = 'AS_FAST_AS_POSSIBLE';
                        $lineItem->creativeRotationType = 'OPTIMIZED';
                    } else if ($campania['type_DFP'] == 'STANDARD') {
                        $lineItem->lineItemType = 'STANDARD';
                        $lineItem->startDateTime = DateTimeUtils::GetDfpDateTime(new DateTime($fecha_inicio));
                        $lineItem->endDateTime = DateTimeUtils::GetDfpDateTime(new DateTime($campania['fecha_fin']));

                        $lineItem->deliveryRateType = 'EVENLY';
                        $lineItem->creativeRotationType = 'OPTIMIZED';
                    }
                }

                if ($campania['modalidad_compra'] == 'cpm') {
                    $lineItem->costType = 'CPM';
                } else if ($campania['modalidad_compra'] == 'cpc') {
                    $lineItem->costType = 'CPC';
                } else if ($campania['modalidad_compra'] == 'cpv') {
                    $lineItem->costType = 'CPM';
                } else {
                    $lineItem->costType = 'CPM';
                }

                $impresiones_clicks = $campania['cantidad'];

                $monto = $campania['valor_unidad'] - (($campania['comision'] * $campania['valor_unidad']) / 100);
                $monto = $monto - (($campania['descuento'] * $campania['valor_unidad']) / 100);

                if ($campania['modalidad_compra'] == 'cpv') {
                    $monto = ($impresiones_clicks * $monto) / (($impresiones_clicks * 2) / 1000);
                    $impresiones_clicks = $impresiones_clicks * 2;
                }

                $monto = $monto * 1000000;

                $lineItem->costPerUnit = new Money('USD', $monto);
                $lineItem->targeting = $targeting;

                $goal = new Goal();
                //Unidades por dia
                $goal->units = $impresiones_clicks;

                if ($lineItem->costType == 'CPM')
                    $goal->unitType = 'IMPRESSIONS';
                if ($lineItem->costType == 'CPC')
                    $goal->unitType = 'CLICKS';

                $goal->goalType = 'DAILY';
                $lineItem->primaryGoal = $goal;

                // frecuencia
                if ($campania['frecuencia'] == '1x24') {
                    $frecuencia = new FrequencyCap(1, 1, 'DAY');
                } else if ($campania['frecuencia'] == '2x24') {
                    $frecuencia = new FrequencyCap(2, 1, 'DAY');
                }

                if ($campania['frecuencia'] != 'NORMAL')
                    $lineItem->frequencyCaps = $frecuencia;

                if ($campania['tipo_campania'] == 'impresiones_mobile')
                    $lineItem->targetPlatform = 'MOBILE';

                $lineItems[] = $lineItem;
            }

            if ($campania['segmentacion_id'] == 2) { // canales tematicos
                $canales_tematicos = $anunciantes->get_campaign_placements($newCampaign['id']);

                foreach ($canales_tematicos as $row) {

                    $targetedPlacements = null;
                    $inventoryTargeting = null;
                    $lineItem = null;
                    $targeting = null;
                    $countryLocations = null;

                    /* TARGETING GENERAL */
                    $targeting = new Targeting();
                    //GEO
                    $geoTargeting = new GeoTargeting();
                    for ($i = 0; $i < sizeof($paises); $i++) {
                        $countryLocation = new DfpLocation();
                        $countryLocation->id = $paises[$i];

                        $countryLocations[] = $countryLocation;
                    }

                    $geoTargeting->targetedLocations = $countryLocations;
                    $targeting->geoTargeting = $geoTargeting;

                    //TECHNOLOGY
                    if ($campania['tipo_campania'] == 'impresiones_mobile') {
                        $technologyTargeting = new TechnologyTargeting();
                        $deviceCategoryTargeting = new DeviceCategoryTargeting();

                        $deviceCategoryTechnology = new Technology();
                        $deviceCategoryTechnology->id = 30000;

                        $deviceCategoryTargeting->excludedDeviceCategories = array($deviceCategoryTechnology);
                        $technologyTargeting->deviceCategoryTargeting = $deviceCategoryTargeting;
                        $targeting->technologyTargeting = $technologyTargeting;
                    }
                    /* FIN TARGETING GENERAL */

                    $inventoryTargeting = new InventoryTargeting();
                    $targetedPlacements[] = $row['id_dfp'];
                    $inventoryTargeting->targetedPlacementIds = $targetedPlacements;
                    $targeting->inventoryTargeting = $inventoryTargeting;

                    $lineItem = new LineItem();

                    $lineItem->orderId = $res_order[0]->id;
                    $lineItem->name = $row['name'];
                    $lineItem->disableSameAdvertiserCompetitiveExclusion = TRUE;
                    $lineItem->creativePlaceholders = $creativePlaceholders;

                    if ($campania['tipo_campania'] == 'pre_roll' || $campania['tipo_campania'] == 'overlay') {
                        $lineItem->environmentType = 'VIDEO_PLAYER';
                    }

                    if ($campania['tipo_campania'] == 'data') {
                        $lineItem->lineItemType = 'SPONSORSHIP';
                        $lineItem->startDateTime = DateTimeUtils::GetDfpDateTime(new DateTime($fecha_inicio));
                        $lineItem->endDateTime = DateTimeUtils::GetDfpDateTime(new DateTime($campania['fecha_fin']));

                        $lineItem->deliveryRateType = 'AS_FAST_AS_POSSIBLE';
                        $lineItem->creativeRotationType = 'OPTIMIZED';
                    } else {
                        if ($campania['type_DFP'] == 'PRICE_PRIORITY') {
                            $lineItem->lineItemType = 'PRICE_PRIORITY';

                            $lineItem->startDateTime = DateTimeUtils::GetDfpDateTime(new DateTime($fecha_inicio));
                            $lineItem->endDateTime = DateTimeUtils::GetDfpDateTime(new DateTime($campania['fecha_fin']));

                            $lineItem->deliveryRateType = 'AS_FAST_AS_POSSIBLE';
                            $lineItem->creativeRotationType = 'OPTIMIZED';
                        } else if ($campania['type_DFP'] == 'STANDARD') {
                            $lineItem->lineItemType = 'STANDARD';
                            $lineItem->startDateTime = DateTimeUtils::GetDfpDateTime(new DateTime($fecha_inicio));
                            $lineItem->endDateTime = DateTimeUtils::GetDfpDateTime(new DateTime($campania['fecha_fin']));

                            $lineItem->deliveryRateType = 'EVENLY';
                            $lineItem->creativeRotationType = 'OPTIMIZED';
                        }
                    }

                    if ($campania['modalidad_compra'] == 'cpm') {
                        $lineItem->costType = 'CPM';
                    } else if ($campania['modalidad_compra'] == 'cpc') {
                        $lineItem->costType = 'CPC';
                    } else if ($campania['modalidad_compra'] == 'cpv') {
                        $lineItem->costType = 'CPM';
                    } else {
                        $lineItem->costType = 'CPM';
                    }

                    $cantidad = $campania['cantidad'];

                    $monto = $campania['valor_unidad'] - (($campania['comision'] * $campania['valor_unidad']) / 100);
                    $monto = $monto - (($campania['descuento'] * $campania['valor_unidad']) / 100);

                    if ($campania['modalidad_compra'] == 'cpv') {
                        $monto = ($cantidad * $monto) / (($cantidad * 2) / 1000);
                        $cantidad = $cantidad * 2;
                    }

                    $cantidad_diaria = round($cantidad / sizeof($canales_tematicos));

                    $monto = $monto * 1000000;

                    $lineItem->costPerUnit = new Money('USD', $monto);
                    $lineItem->targeting = $targeting;

                    $goal = new Goal();
                    //Unidades por dia
                    $goal->units = $cantidad_diaria;

                    if ($lineItem->costType == 'CPM')
                        $goal->unitType = 'IMPRESSIONS';
                    if ($lineItem->costType == 'CPC')
                        $goal->unitType = 'CLICKS';

                    $goal->goalType = 'DAILY';
                    $lineItem->primaryGoal = $goal;

                    // frecuencia
                    if ($campania['frecuencia'] == '1x24') {
                        $frecuencia = new FrequencyCap(1, 1, 'DAY');
                    } else if ($campania['frecuencia'] == '2x24') {
                        $frecuencia = new FrequencyCap(2, 1, 'DAY');
                    }

                    if ($campania['frecuencia'] != 'NORMAL')
                        $lineItem->frequencyCaps = $frecuencia;

                    if ($campania['tipo_campania'] == 'impresiones_mobile')
                        $lineItem->targetPlatform = 'MOBILE';

                    $lineItems[] = $lineItem;
                }
            }

            if ($campania['segmentacion_id'] == 3) { // sitios especificos
                $sitios = $anunciantes->get_campaign_sites($newCampaign['id']);
                foreach ($sitios as $row) {
                    //echo "Sitio: " . $row['id_dfp'] . " - " . $row['name'] . "\n";

                    $targetedAdUnits = null;
                    $targetedAdUnit = null;
                    $inventoryTargeting = null;
                    $lineItem = null;
                    $targeting = null;
                    $countryLocations = null;

                    /* TARGETING GENERAL */
                    $targeting = new Targeting();
                    //GEO
                    $geoTargeting = new GeoTargeting();
                    for ($i = 0; $i < sizeof($paises); $i++) {
                        $countryLocation = new DfpLocation();
                        $countryLocation->id = $paises[$i];

                        $countryLocations[] = $countryLocation;
                    }

                    $geoTargeting->targetedLocations = $countryLocations;
                    $targeting->geoTargeting = $geoTargeting;

                    //TECHNOLOGY
                    if ($campania['tipo_campania'] == 'impresiones_mobile') {
                        $technologyTargeting = new TechnologyTargeting();
                        $deviceCategoryTargeting = new DeviceCategoryTargeting();

                        $deviceCategoryTechnology = new Technology();
                        $deviceCategoryTechnology->id = 30000;

                        $deviceCategoryTargeting->excludedDeviceCategories = array($deviceCategoryTechnology);
                        $technologyTargeting->deviceCategoryTargeting = $deviceCategoryTargeting;
                        $targeting->technologyTargeting = $technologyTargeting;
                    }
                    /* FIN TARGETING GENERAL */

                    $targetedAdUnit = new AdUnitTargeting();
                    $targetedAdUnit->adUnitId = $row['id_dfp'];
                    $targetedAdUnits[] = $targetedAdUnit;

                    $inventoryTargeting = new InventoryTargeting();
                    $inventoryTargeting->targetedAdUnits = $targetedAdUnits;
                    $targeting->inventoryTargeting = $inventoryTargeting;

                    $lineItem = new LineItem();

                    $lineItem->orderId = $res_order[0]->id;
                    $lineItem->name = $row['name'];
                    $lineItem->disableSameAdvertiserCompetitiveExclusion = TRUE;
                    $lineItem->creativePlaceholders = $creativePlaceholders;
                    $lineItem->targeting = $targeting;

                    if ($campania['tipo_campania'] == 'pre_roll' || $campania['tipo_campania'] == 'overlay') {
                        $lineItem->environmentType = 'VIDEO_PLAYER';
                    }

                    if ($campania['tipo_campania'] == 'data') {
                        $lineItem->lineItemType = 'SPONSORSHIP';
                        $lineItem->startDateTime = DateTimeUtils::GetDfpDateTime(new DateTime($fecha_inicio));
                        $lineItem->endDateTime = DateTimeUtils::GetDfpDateTime(new DateTime($campania['fecha_fin']));

                        $lineItem->deliveryRateType = 'AS_FAST_AS_POSSIBLE';
                        $lineItem->creativeRotationType = 'OPTIMIZED';
                    } else {
                        if ($campania['type_DFP'] == 'PRICE_PRIORITY') {
                            $lineItem->lineItemType = 'PRICE_PRIORITY';

                            $lineItem->startDateTime = DateTimeUtils::GetDfpDateTime(new DateTime($fecha_inicio));
                            $lineItem->endDateTime = DateTimeUtils::GetDfpDateTime(new DateTime($campania['fecha_fin']));

                            $lineItem->deliveryRateType = 'AS_FAST_AS_POSSIBLE';
                            $lineItem->creativeRotationType = 'OPTIMIZED';
                        } else if ($campania['type_DFP'] == 'STANDARD') {
                            $lineItem->lineItemType = 'STANDARD';
                            $lineItem->startDateTime = DateTimeUtils::GetDfpDateTime(new DateTime($fecha_inicio));
                            $lineItem->endDateTime = DateTimeUtils::GetDfpDateTime(new DateTime($campania['fecha_fin']));

                            $lineItem->deliveryRateType = 'EVENLY';
                            $lineItem->creativeRotationType = 'OPTIMIZED';
                        }
                    }

                    if ($campania['modalidad_compra'] == 'cpm') {
                        $lineItem->costType = 'CPM';
                    } else if ($campania['modalidad_compra'] == 'cpc') {
                        $lineItem->costType = 'CPC';
                    } else if ($campania['modalidad_compra'] == 'cpv') {
                        $lineItem->costType = 'CPM';
                    } else {
                        $lineItem->costType = 'CPM';
                    }

                    $cantidad = $campania['cantidad'];

                    $monto = $campania['valor_unidad'] - (($campania['comision'] * $campania['valor_unidad']) / 100);
                    $monto = $monto - (($campania['descuento'] * $campania['valor_unidad']) / 100);

                    if ($campania['modalidad_compra'] == 'cpv') {
                        $monto = ($cantidad * $monto) / (($cantidad * 2) / 1000);
                        $cantidad = $cantidad * 2;
                    }

                    $cantidad_diaria = round($cantidad / sizeof($sitios));

                    $monto = $monto * 1000000;

                    $lineItem->costPerUnit = new Money('USD', $monto);

                    $goal = new Goal();
                    $goal->units = $cantidad_diaria;

                    if ($lineItem->costType == 'CPM')
                        $goal->unitType = 'IMPRESSIONS';
                    if ($lineItem->costType == 'CPC')
                        $goal->unitType = 'CLICKS';

                    $goal->goalType = 'DAILY';
                    $lineItem->primaryGoal = $goal;

                    // frecuencia
                    if ($campania['frecuencia'] == '1x24') {
                        $frecuencia = new FrequencyCap(1, 1, 'DAY');
                    } else if ($campania['frecuencia'] == '2x24') {
                        $frecuencia = new FrequencyCap(2, 1, 'DAY');
                    }

                    if ($campania['frecuencia'] != 'NORMAL')
                        $lineItem->frequencyCaps = $frecuencia;

                    if ($campania['tipo_campania'] == 'impresiones_mobile')
                        $lineItem->targetPlatform = 'MOBILE';


                    $lineItems[] = $lineItem;

                    unset($lineItem);
                }
            }

            $lineItems = $lineItemService->createLineItems($lineItems);

            return ['type' => true, 'data' => $res_order[0]->id];
        } catch (Exception $ex) {
            return ['type' => false, 'data' => $ex];
        }
    }

    public function updateLineItem($credentials, $newLineItem) {
        try {

            $user = $this->_dfpUser($credentials);
            $user->LogDefaults();
            $lineItemService = $this->_service($user, 'LineItem');

            $statementBuilder = new StatementBuilder();
            $statementBuilder->Where('id = :id')
                    ->OrderBy('id ASC')
                    ->Limit(1)
                    ->WithBindVariableValue('id', $newLineItem['lineitem_id']);

            // Get the line item.
            $page = $lineItemService->getLineItemsByStatement(
                    $statementBuilder->ToStatement());
            $lineItem = $page->results[0];


            //$lineItem = new LineItem();
            $inventoryTargeting = new InventoryTargeting();

            $array_adunits = null;

            $adunits = $newLineItem['adunits'];

            for ($i = 0; $i < sizeof($adunits); $i++) {
                $adunit_id = $adunits[$i];

                $adUnitTargeting = new AdUnitTargeting();
                $adUnitTargeting->adUnitId = $adunit_id;

                $array_adunits[] = $adUnitTargeting;
            }

            //$inventoryTargeting->targetedAdUnits = array($adUnitTargeting, $adUnitTargeting2, $adUnitTargeting3);
            $inventoryTargeting->targetedAdUnits = $array_adunits;
            $inventoryTargeting->includeDescendants = true;

            $targeting = new Targeting();
            $targeting->inventoryTargeting = $inventoryTargeting;
            $lineItem->targeting = $targeting;

            $lineItems = $lineItemService->updateLineItems(array($lineItem));


            /* foreach ($lineItems as $updatedLineItem) {
              printf("Line item with ID %d, name '%s' was updated.\n", $updatedLineItem->id, $updatedLineItem->name);
              } */

            // Display results.
            if (isset($lineItems)) {
                return ['type' => true, 'data' => $lineItems[0]];
            } else {
                throw new Exception("No line items created.");
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function newCreative($credentials, $data) {
        try {
            $user = $this->_dfpUser($credentials);
            //$user->LogDefaults();
            $creativeService = $this->_service($user, 'Creative');

            //$creative = new Creative();

            $vars = MapUtils::GetMapEntries(array('id' => new NumberValue(77171601886)));
            $filterStatement = new Statement("WHERE id = :id", $vars);

            $page = $creativeService->getCreativesByStatement($filterStatement);
            echo '<pre>';
            dd($page);
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function getReport($credentials, $data) {
        try {
            
            $user = $this->_dfpUser($credentials);
            $user->LogDefaults();
            $inventoryService = $this->_service($user, 'Report');

            // Get the effective root ad unit's ID for all ad units to be created under.
            $network = $this->_getNetwork($user);
            $effectiveRootAdUnitId = $network->effectiveRootAdUnitId;
            // Create statement to filter on a parent ad unit with the root ad unit ID to
            // include all ad units in the network.
            //$statementBuilder = new StatementBuilder();
            //$statementBuilder->Where($data['filter']);
            $statementBuilder = new Statement();

            if (isset($data['filter']))
                $statementBuilder->query = "WHERE " . $data['filter'];

            // Create report query.
            $reportQuery = new ReportQuery();
            $reportQuery->dimensions = $data['groupby'];
            $reportQuery->columns = $data['columns'];

            if (isset($data['dimensionAttributes']))
                $reportQuery->dimensionAttributes = $data['dimensionAttributes'];

            if (isset($data['dimensionFilters']))
                $reportQuery->dimensionFilters = $data['dimensionFilters'];

            // Set the filter statement.
            $reportQuery->statement = $statementBuilder;

            // Set the ad unit view to hierarchical.
            if (!isset($data['order']))
                $reportQuery->adUnitView = 'FLAT';

            // Set the start and end dates or choose a dynamic date range type.
            $reportQuery->dateRangeType = $data['date'];
            if ($data['date'] == "CUSTOM_DATE") {
                list($dia_desde, $mes_desde, $anio_desde) = explode("-", $data['startDate']);
                list($dia_hasta, $mes_hasta, $anio_hasta) = explode("-", $data['endDate']);

                $start_date = new Date($anio_desde, $mes_desde, $dia_desde);
                $end_date = new Date($anio_hasta, $mes_hasta, $dia_hasta);

                $reportQuery->startDate = $start_date;
                $reportQuery->endDate = $end_date;
            }
            // Create report job.
            $reportJob = new ReportJob();
            $reportJob->reportQuery = $reportQuery;
            // Run report job.
            $reportJob = $inventoryService->runReportJob($reportJob);
            // Create report downloader.
            $reportDownloader = new ReportDownloader($inventoryService, $reportJob->id);
            // Wait for the report to be ready.
            $reportDownloader->waitForReportReady();

            // Change to your file location.
            $filePath = sprintf('%s.txt.gz', tempnam(public_path() . "/tempfiles/", "report"));

            // Download the report.
            $reportDownloader->downloadReport('TSV', $filePath);

            return $filePath;
        } catch (Exception $ex) {
            return $ex;
        }
    }

    private function _getNetwork($user) {
// Get the NetworkService.
        $networkService = $this->_service($user, 'Network');

// Get the effective root ad unit's ID for all ad units to be created under.
        $network = $networkService->getCurrentNetwork();
        return $network;
    }

    private function _getAllNetwork($user) {
// Get the NetworkService.
        $networkService = $this->_service($user, 'Network');

// Get the effective root ad unit's ID for all ad units to be created under.
        return $networkService->getAllNetworks();
    }

    private function _service($user, $service) {
        return $user->GetService($service . 'Service', self::VERSION);
    }

    private function _dfpUser($credentials) {
        Verification::isExist($credentials, 'name', TRUE);
        Verification::isExist($credentials, 'code', FALSE);
        Verification::isExist($credentials, 'oauth2info', TRUE);
        //$user = new DfpUser(NULL, NULL, NULL, $credentials['name'], $credentials['code'], NULL, NULL, $credentials['oauth2info']);
        $user = new DfpUser();
        return $user;
    }

    private function _getOAuth2Credential($user) {
        $redirectUri = NULL;
        $offline = TRUE;
// Get the authorization URL for the OAuth2 token.
// No redirect URL is being used since this is an installed application. A web
// application would pass in a redirect URL back to the application,
// ensuring it's one that has been configured in the API console.
// Passing true for the second parameter ($offline) will provide us a refresh
// token which can used be refresh the access token when it expires.
        $OAuth2Handler = $user->GetOAuth2Handler();

        $authorizationUrl = $OAuth2Handler->GetAuthorizationUrl(
                $user->GetOAuth2Info(), $redirectUri, $offline);

// In a web application you would redirect the user to the authorization URL
// and after approving the token they would be redirected back to the
// redirect URL, with the URL parameter "code" added. For desktop
// or server applications, spawn a browser to the URL and then have the user
// enter the authorization code that is displayed.
        return $authorizationUrl;
    }

    public function getToken($credentials, $code) {
        return $this->_getToken($credentials, $code);
    }

    private function _getToken($credentials, $code) {
        $user = $this->_dfpUser($credentials);

        $redirectUri = NULL;
        $offline = TRUE;
// Get the authorization URL for the OAuth2 token.
// No redirect URL is being used since this is an installed application. A web
// application would pass in a redirect URL back to the application,
// ensuring it's one that has been configured in the API console.
// Passing true for the second parameter ($offline) will provide us a refresh
// token which can used be refresh the access token when it expires.
        $OAuth2Handler = $user->GetOAuth2Handler();

        /* $authorizationUrl = $OAuth2Handler->GetAuthorizationUrl(
          $user->GetOAuth2Info(), $redirectUri, $offline);
         */
// In a web application you would redirect the user to the authorization URL
// and after approving the token they would be redirected back to the
// redirect URL, with the URL parameter "code" added. For desktop
// or server applications, spawn a browser to the URL and then have the user
// enter the authorization code that is displayed.
        //return $authorizationUrl;
        //$code = '4/79ASCai_iiDzsvYWSTTMV43RiQ79pEsiuVR_jrPfEtE.8i1b_DwdLC4TJvIeHux6iLZH_XZ7kwI';
// Get the access token using the authorization code. Ensure you use the same
// redirect URL used when requesting authorization.
        $user->SetOAuth2Info(
                $OAuth2Handler->GetAccessToken(
                        $user->GetOAuth2Info(), $code, $redirectUri));

// The access token expires but the refresh token obtained for offline use
// doesn't, and should be stored for later use.
        return $user->GetOAuth2Info();
    }

}
