<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Description of Validador
 *
 * @author eislas
 */
class Validador {

    function __construct($textoXml) {
        $this->textoXml = $textoXml;
        $this->xml = new \DOMDocument();
        $ok = $this->xml->loadXML($textoXml);
        if (!$ok) {
//            \display_xml_errors();
            $errors = libxml_get_errors();
            throw new \Exception(implode("\n", $errors));
        } else {
            $this->loadData();
            $this->initConfig();
        }
    }

    /**
     * Valida estructura contra esquema correspondiente
     */
    function validarEstructura() {
        /*
         * Todos los archivos que se requieren para hacer la validacion
         * fueron descargados del portal del SAT pero los tengo localmente
         * almacenados en mi maquina para que las validaciones sean mas rapidas.
         * Ademas el archivo prinicpal cfdv32.xsd esta 'un poco' modifcado para
         * que importe los complementos
         *
         * */
        libxml_use_internal_errors(true);   // Gracias a Salim Giacoman
        if ($this->data['tipo'] == "retenciones") {
            switch ($this->data['version']) {
                case "1.0":
                    $ok = $this->xml->schemaValidate("xsd/retencionpagov1.xsd");
                    break;
                default:
                    $ok = false;
            }
        } else {
            switch ($this->data['version']) {
                case "2.0":
                    $ok = $this->xml->schemaValidate(DATA_PATH . "/xsd/cfdv2complemento.xsd");
                    break;
                case "2.2":
                    $ok = $this->xml->schemaValidate(DATA_PATH . "/xsd/cfdv22complemento.xsd");
                    break;
                case "3.0":
                    $ok = $this->xml->schemaValidate(DATA_PATH . "/xsd/cfdv3complemento.xsd");
                    break;
                case "3.2":
                    $ok = $this->xml->schemaValidate(DATA_PATH . "/xsd/cfdv32.xsd");
                    break;
                case "3.3":
                    $ok = $this->xml->schemaValidate(DATA_PATH . "/xsd/cfdv33.xsd");
                    break;
                case "4.0":
                    $ok = $this->xml->schemaValidate(DATA_PATH . "/xsd/4.0/cfdv40.xsd");
                    break;
                default:
                    $ok = false;
            }
        }
        if ($ok) {
            return TRUE;
        } else {
            $errors = libxml_get_errors();
//            throw new \Exception($errors);
            return $errors;
        }
    }

// }}} Valida XSD
    function loadData() {
        if (strpos($this->textoXml, "cfdi:Comprobante") !== FALSE) {
            $this->tipo = "cfdi";
        } elseif (strpos($this->textoXml, "<Comprobante") !== FALSE) {
            $this->tipo = "cfd";
        } elseif (strpos($this->textoXml, "retenciones:Retenciones") !== FALSE) {
            $this->tipo = "retenciones";
        } else {
            die("Tipo de XML no identificado ....".$this->textoXml);
        }
////////////////////////////////////////////////////////////////////////////
//   Con el arbol DOM buscamos los atributos
        if ($this->tipo == "retenciones") {
            $root = $this->xml->getElementsByTagName('Retenciones')->item(0);
            $Version = $root->getAttribute("Version");
        } else {
            $root = $this->xml->getElementsByTagName('Comprobante')->item(0);
            $version = $root->getAttribute("version");
            if ($version == null)
                $version = $root->getAttribute("Version");
        }
        $Receptor = $root->getElementsByTagName('Receptor')->item(0);
        $Emisor = $root->getElementsByTagName('Emisor')->item(0);
        $this->data['seri'] = $root->getAttribute("serie");
        $this->data['fecha'] = $root->getAttribute("fecha");
        $this->data['noap'] = $root->getAttribute("noAprobacion");
        $this->data['anoa'] = $root->getAttribute("anoAprobacion");
        $this->data['tipo'] = $this->tipo;
        if ($this->tipo == "retenciones") {
            $rfc = $Emisor->getAttribute('RFCEmisor');
            $this->data['rfc'] = utf8_decode($rfc);
            $rfc = $Receptor->getAttribute('RFCRecep');
            $this->data['rfc_receptor'] = utf8_decode($rfc);
            $this->data['version'] = $root->getAttribute("Version");
            $this->data['no_cert'] = $root->getAttribute("NumCert");
            $this->data['cert'] = $root->getAttribute("Cert");
            $this->data['sell'] = $root->getAttribute("Sello");
            $Totales = $root->getElementsByTagName('Totales')->item(0);
            $this->data['total'] = $Totales->getAttribute("montoTotGrav");
        } else {
            $this->data['version'] = $version;
            if ($version == "3.3" || $version == "4.0") { // Mayusculas
                $this->data['total'] = $root->getAttribute('Total');
                $this->data['no_cert'] = $root->getAttribute('NoCertificado');
                $this->data['cert'] = $root->getAttribute('Certificado');
                $this->data['sell'] = $root->getAttribute('Sello');
                $rfc = $Emisor->getAttribute('Rfc');
                $this->data['rfc'] = utf8_decode($rfc);
                $rfc = $Receptor->getAttribute('Rfc');
                $this->data['rfc_receptor'] = utf8_decode($rfc);
            } else { // NO es 3.3, es 3.2 o anterior minusculas
                $this->data['total'] = $root->getAttribute('total');
                $this->data['no_cert'] = $root->getAttribute('noCertificado');
                $this->data['cert'] = $root->getAttribute('certificado');
                $this->data['sell'] = $root->getAttribute('sello');
                $rfc = $Emisor->getAttribute('rfc');
                $this->data['rfc'] = utf8_decode($rfc);
                $rfc = $Receptor->getAttribute('rfc');
                $this->data['rfc_receptor'] = utf8_decode($rfc);
            } // version 3.3
        } // Retencion o CFDI
        $TFD = $root->getElementsByTagName('TimbreFiscalDigital')->item(0);
        if ($TFD != null) {
            $this->data['version_tfd'] = $TFD->getAttribute("Version");
            if ($this->data['version_tfd'] == "")
                $this->data['version_tfd'] = $TFD->getAttribute("version");
            if ($this->data['version_tfd'] == "1.0") {
                $this->data['sellocfd'] = $TFD->getAttribute("selloCFD");
                $this->data['sellosat'] = $TFD->getAttribute("selloSAT");
                $this->data['no_cert_sat'] = $TFD->getAttribute("noCertificadoSAT");
            } else {
                $this->data['sellocfd'] = $TFD->getAttribute("SelloCFD");
                $this->data['sellosat'] = $TFD->getAttribute("SelloSAT");
                $this->data['no_cert_sat'] = $TFD->getAttribute("NoCertificadoSAT");
            }
            $this->data['uuid'] = $TFD->getAttribute("UUID");
        } else {
            $this->data['sellocfd'] = null;
            $this->data['sellosat'] = null;
            $this->data['no_cert_sat'] = null;
            $this->data['uuid'] = null;
        }
    }

    function initConfig() {
        $this->xsl = new \DOMDocument;
        if ($this->data['tipo'] == "retenciones") {
            switch ($this->data['version']) {
                case "1.0":
                    $this->xsl->load(DATA_PATH . '/xslt/retenciones.xslt');
                    $this->algo = OPENSSL_ALGO_SHA1;
                    break;
                default:
                    echo "version incorrecta " . $this->data['tipo'] . " " . $this->data['version'] . "\n";
                    break;
            }
        } else {
            switch ($this->data['version']) {
                case "2.0":
                    $this->xsl->load(DATA_PATH . '/xslt/cadenaoriginal_2_0.xslt');
                    if (substr($this->data['fecha'], 0, 4) < 2011) {
                        $this->algo = OPENSSL_ALGO_MD5;
                    } else {
                        $this->algo = OPENSSL_ALGO_SHA1;
                    }
                    break;
                case "2.2":
                    $this->xsl->load(DATA_PATH . '/xslt/cadenaoriginal_2_2.xslt');
                    $this->algo = OPENSSL_ALGO_SHA1;
                    break;
                case "3.0":
                    $this->xsl->load(DATA_PATH . '/xslt/cadenaoriginal_3_0.xslt');
                    if (substr($this->data['fecha'], 0, 4) < 2011) {
                        $this->algo = OPENSSL_ALGO_MD5;
                    } else {
                        $this->algo = OPENSSL_ALGO_SHA1;
                    }
                    break;
                case "3.2":
                    $this->xsl->load(DATA_PATH . '/xslt/cadenaoriginal_3_2.xslt');
                    $this->algo = OPENSSL_ALGO_SHA1;
                    break;
                case "3.3":
                    $this->xsl->load(DATA_PATH . '/xslt/cadenaoriginal_3_3.xslt');
                    $this->algo = OPENSSL_ALGO_SHA256;
                    break;
                case "4.0":
                    $this->xsl->load(DATA_PATH . '/xslt/4.0/cadenaoriginal_4_0.xslt');
                    $this->algo = OPENSSL_ALGO_SHA256;
                    break;
                default:
                    echo "version incorrecta " . $this->data['tipo'] . " " . $this->data['version'] . "\n";
                    break;
            }
        }
        $proc = new \XSLTProcessor;
        $proc->importStyleSheet($this->xsl);
        $this->cadena = $proc->transformToXML($this->xml);
        var_dump($this->cadena);
    }

    function validarSello() {
        $pem = (is_string($this->data['cert'])) ? $this->data['cert'] : $this->data['cert'][0];
        $pem = preg_replace("/[\n|\r|\n\r]/", '', $pem);
        $pem = preg_replace('/\s\s+/', '', $pem);
        $cert = "-----BEGIN CERTIFICATE-----\n" . chunk_split($pem, 64) . "-----END CERTIFICATE-----\n";
        $certId = openssl_x509_read($cert);
        if (!$certId) {
            throw new \ExceptionMessage('No hay certificado para validar el sello.');
        }
        $pubkeyid = openssl_get_publickey($certId);
        if (!$pubkeyid) {
            throw new \ExceptionMessage('No se puede obtener la llave publica del certificado.');
        }
        $ok = openssl_verify($this->cadena, base64_decode($this->data['sell']), $pubkeyid, $this->algo);
        if ($ok == 1) {
//            echo "<h3>Sello ok</h3>";
            return TRUE;
        } else {
            echo '<pre>';
            while ($msg = openssl_error_string()) {
                echo $msg . "\n";
            }
            echo '</pre>';
            throw new \ExceptionMessage('Sello incorrecto.');
            return false;
        }
        openssl_free_key($pubkeyid);
    }

}
