<?php
namespace Hacienda;

require(dirname(__FILE__,2) .'/xmlseclibs/xmlseclibs.php');

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 *
 * xmlseclibs.php is a library written in PHP for working with XML Encryption and Signatures.
 *
 * The author of xmlseclibs is Rob Richards. Please see the license for xmlseclibs.
 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 * hacienda.php
 *
 * Copyright 2019 Enzo Jiménez <enzofjh@gmail.com>
 *
 * This class <Firmador> is an extended modification of xmlseclibs.php that has been improved
 * to be used as free software for signing Electronic Invoices in Costa Rica to comply with
 * government laws and protocols by using PHP as the main programming language.
 *
 * You can redistribute it and/or modify it under the terms of the
 * GNU Affero General Public License as published by the Free Software Foundation,
 * either version 3 of the License, or (at your option) any later version, and also under
 * the terms of xmlseclibs licensing.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    2019 Enzo Jiménez <enzofjh@gmail.com>
 */

class Firmador {

    const FROM_XML_STRING = 1;
    const FROM_XML_FILE = 2;
    const TO_BASE64_STRING = 3;
    const TO_XML_STRING = 4;
    const TO_XML_FILE = 5;

    public function firmarXml($pfx,$pin,$input,$output,$path=null){

        // =========================
        // LOAD CERTIFICATE (FIXED)
        // =========================
        $pfxContent = file_get_contents($pfx);

        $certs = [];
        if (!openssl_pkcs12_read($pfxContent, $certs, $pin)) {
            return [
                "error" => "openssl_pkcs12_read failed",
                "openssl_error" => openssl_error_string()
            ];
        }

        if (!isset($certs['pkey']) || !isset($certs['cert'])) {
            return [
                "error" => "Certificate missing pkey or cert"
            ];
        }

        // =========================
        // LOAD XML
        // =========================
        $xml = new \DOMDocument();

        try {
            $xml->loadXML($input);
        } catch (\Exception $ex){
            return ["error" => $ex->getMessage()];
        }

        // =========================
        // SIGN PROCESS
        // =========================
        $objSec = new XMLSecurityDSig();

        // Mantener el primer nodo secundario original XML en memoria
        $objSec->xmlFirstChild = $xml->firstChild;

        // Establecer política de firma
        $objSec->setSignPolicy();

        // Usar la canonicalización exclusiva de c14n.
        $objSec->setCanonicalMethod($objSec::C14N);

        // Cargar la clave privada del certificado
        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $objKey->loadKey($certs['pkey'], false);

        // Add cert
        $objSec->add509Cert($certs['cert'], true);

        // Build minimal certInfo structure for compatibility
        $certInfo = [
            "privateKey" => $certs['pkey'],
            "publicKey" => $certs['cert']
        ];

        $objSec->appendKeyValue($certInfo);

        // Insertar objeto Xades en la firma.
        $objSec->appendXades($certInfo);

        // References
        $objSec->addReference(
            $xml,
            $objSec::SHA256,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature'],
            ['id_ref' => $objSec->reference0Id, 'force_uri' => true]
        );

        $objSec->addReference(
            $objSec->getKeyInfoNode(),
            $objSec::SHA256,
            null,
            ['id_ref' => $objSec->reference1Id, 'force_uri' => false, 'overwrite' => false]
        );

        $objSec->addReference(
            $objSec->getXadesNode(),
            $objSec::SHA256,
            null,
            [
                'force_uri' => false,
                'overwrite' => false,
                "type" => "http://uri.etsi.org/01903#SignedProperties"
            ],
            [
                ['qualifiedName' => 'xmlns:xades', 'value' => $objSec::XADES]
            ]
        );

        // Firma el archivo xml
        $objSec->sign($objKey);
        $objSec->appendSignature($xml->documentElement);

        // =========================
        // OUTPUT
        // =========================
        if ($output == self::TO_BASE64_STRING){
            // Devuelve el string del archivo xml firmado en formato Base64
            return base64_encode($xml->saveXML());
        } else if ($output == self::TO_XML_STRING){
            // Devuelve el archivo xml firmado en formato string Xml
            return $xml->saveXML();
        } else if ($output == self::TO_XML_FILE){
            // Guarda el xml firmado en la ruta especificada y devuelve el resultado
            if (!is_null($path)) {
                return $xml->save($path);
            } else {
                return false;
            }
        }
    }
}
