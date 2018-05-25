<?php

class Anunciantes {

    public function _connect_db() {
        $mysqli = mysqli_connect('205.186.153.231', 'produccion', 'prod_2013', 'produccion_mediafem');
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        return $mysqli;
    }

    public function _connect_adt_db() {
        $mysqli = mysqli_connect('205.186.153.231', 'prod_adt', 'v0_T5l9q', 'prod_adt_2');
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        return $mysqli;
    }

    function get_campaign($campaign_id) {
        $mysqli = $this->_connect_db();

        $sql = "select a.id as id_anunciante_mf, a.nombre as nombre_anunciante, a.id_dfp as id_anunciante_dfp, c.*
                                                from campania c, anunciantes_adservers a 
                                                where c.id = $campaign_id 
                                                and a.id = c.id_anunciante;";

        $res_select_campaign = mysqli_query($mysqli, $sql);

        $row_campaign = $res_select_campaign->fetch_assoc();

        return $row_campaign;
    }

    function get_campaign_countries($campaign_id) {
        $mysqli = $this->_connect_db();

        $countries = null;

        $sql = "select cp.id_campania, cp.id_pais, p.descripcion, p.id_dfp from campanias_paises cp, paises p
                where cp.id_campania = $campaign_id
                and cp.id_pais = p.id;";

        if ($result = $mysqli->query($sql)) {
            while ($obj = $result->fetch_object()) {
                $countries[] = $obj->id_dfp;
            }
        }

        return $countries;
    }

    function get_campaign_sizes($campaign_id) {
        $mysqli = $this->_connect_db();

        $sizes = null;

        $sql = "select cf.id_formato, f.descripcion, f.valor, f.width, f.height 
                from campanias_formatos cf, formatos_dfp f
                where cf.id_campania = $campaign_id
                and cf.id_formato = f.id;";

        if ($result = $mysqli->query($sql)) {
            while ($obj = $result->fetch_object()) {
                $sizes[] = array('id' => $obj->id_formato, 'name' => $obj->descripcion, 'value' => $obj->valor, 'width' => $obj->width, 'height' => $obj->height);
            }
        }

        return $sizes;
    }

    function get_campaign_placements($campaign_id) {
        $mysqli = $this->_connect_db();

        $sql = "select cc.id_canal_tematico, c.id_dfp, c.nombre from campanias_canales_tematicos cc, categorias c
                where cc.id_campania = $campaign_id
                and c.id = cc.id_canal_tematico;";

        $placements = null;

        if ($result = $mysqli->query($sql)) {
            while ($obj = $result->fetch_object()) {
                $placements[] = array('id' => $obj->id_canal_tematico, 'id_dfp' => $obj->id_dfp, 'name' => $obj->nombre);
            }
        }

        return $placements;
    }

    function get_campaign_sites($campaign_id) {
        $mysqli = $this->_connect_db();
        $mysqli_adt = $this->_connect_adt_db();

        $sql = "select id_sitio from campanias_sitios where id_campania = $campaign_id;";

        $sites = null;

        if ($result = $mysqli->query($sql)) {
            while ($obj = $result->fetch_object()) {
                
                $sql_adt = "select s.sit_name from sites s, adserver_site sa where sa.adv_sit_adserver_key = " . $obj->id_sitio . " and s.sit_id = sa.site_id;";

                $res_select_site = mysqli_query($mysqli_adt, $sql_adt);
                $row_site = $res_select_site->fetch_assoc();
                
                $sites[] = array('id_dfp' => $obj->id_sitio, 'name' => $row_site['sit_name']);
            }
        }
        return $sites;
    }

    function update_adververtiser($advertiser) {
        $mysqli = $this->_connect_db();

        $sql = "UPDATE anunciantes_adservers SET id_dfp=" . $advertiser['id_dfp'] . " WHERE  id=" . $advertiser['id_mf'] . ";";

        $mysqli->query($sql);

        return true;
    }

    function update_campaign($campaign) {
        $mysqli = $this->_connect_db();
    }

}
