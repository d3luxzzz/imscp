<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by i-MSCP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get post value
 *
 * @param string $id Data identifier
 * @param string $defaultValue Value returned in case Data has not been found in $_POST
 * @return string
 */
function client_getPost($id, $defaultValue = '')
{
    if (isset($_POST[$id])) {
        return clean_input($_POST[$id]);
    }

    return $defaultValue;
}

/**
 * Validate name field of a DNS resource record
 *
 * @param string $name Name
 * @param string $type DNS record type
 * @param string &$errorString Error string
 * @return bool TRUE if name is valid, FALSE otherwise
 */
function client_validate_NAME($name, $type, &$errorString)
{
    if ($name === '') {
        $errorString .= tr('`%s` field cannot be empty.', tr('Name'));
        return false;
    }

    if ($type == 'TXT' || $type == 'CNAME') {
        $name = preg_replace('/_/', '', $name); // Only to fullfit DMARC/DKIM records validation
    }

    if (!isValidDomainName($name)) {
        $errorString .= tr('Invalid `%s` field.', tr('Name'));
        return false;
    }

    return true;
}

/**
 * Validate rdata field for a CNAME DNS resource record
 *
 * @param string $cname Cname
 * @param string &$errorString Error string
 * @return bool TRUE if cname is valid, FALSE otherwise
 */
function client_validate_CNAME($cname, &$errorString)
{
    if ($cname === '') {
        $errorString .= tr('`%s` field cannot be empty.', tr('Canonical name'));
        return false;
    }

    if ($cname != '@' && !isValidDomainName($cname)) {
        $errorString .= tr('Invalid `%s` field.', tr('Canonical name'));
        return false;
    }

    return true;
}

/**
 * Validate rdata field for a A DNS resource record
 *
 * @param string $ip IPv4 address
 * @param string &$errorString Error string
 * @return bool
 */
function client_validate_A($ip, &$errorString)
{
    if ($ip === '') {
        $errorString .= tr('`%s` field cannot be empty.', tr('IP address'));
        return false;
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
        $errorString .= tr('Invalid `%s` field.', tr('IP address'));
        return false;
    }

    return true;
}

/**
 * Validate rdata field for a AAAA DNS resource record
 *
 * @param array $ip IPv6 address
 * @param string &$errorString Reference to variable, which contain error string
 * @return bool TRUE if the record is valid, FALSE otherwise
 */
function client_validate_AAAA($ip, &$errorString)
{
    if ($ip === '') {
        $errorString .= tr('`%s` field cannot be empty.', tr('IPv6 address'));
        return false;
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
        $errorString .= tr('Invalid `%s` field.', tr('IPv6 address'));
        return false;
    }

    return true;
}

/**
 * Validate rdata field for MX DNS resource record
 *
 * @param string $pref MX preference
 * @param string $host MX host
 * @param string &$errorString Reference to variable, which contain error string
 * @return bool TRUE if the record is valid, FALSE otherwise
 */
function client_validate_MX($pref, $host, &$errorString)
{
    if (!is_number($pref) || $pref > 65535) {
        showBadRequestErrorPage();
    }

    if ($host === '') {
        $errorString .= tr('`%s` field cannot be empty.', tr('Host'));
        return false;
    }

    if (!isValidDomainName($host)) {
        $errorString .= tr('Invalid `%s` field.', tr('Host'));
        return false;
    }

    return true;
}

/**
 * Validate rdata field for SPF/TXT DNS resource record
 *
 * @param string $data DNS record data
 * @param string &$errorString Reference to variable, which contain error string
 * @return bool TRUE if the record is valid, FALSE otherwise
 */
function client_validate_TXT($data, &$errorString)
{
    if ($data === '') {
        $errorString .= tr('`%s` field cannot be empty.', tr('Data'));
        return false;
    }

    if (!preg_match('/^([a-zA-Z0-9\+\?\-\*_~=:. \/;@])+$/', str_replace('"', '', $data))) {
        $errorString .= tr('Invalid `%s` field.', tr('Data'));
        return false;
    }

    return true;
}

/**
 * Validate SRV DNS record
 *
 * @param string $srvName Service name
 * @param string $proto Protocol
 * @param string $priority Priority
 * @param string $weight Weight
 * @param int $port Port
 * @param string $host Target host
 * @param string $errorString Error string
 * @return bool
 */
function client_validate_SRV($srvName, $proto, $priority, $weight, $port, $host, &$errorString)
{
    if ($srvName === '') {
        $errorString .= tr('`%s` field cannot be empty.', tr('Service name'));
        return false;
    }

    if (!preg_match('/^_[a-z0-9]+/i', $srvName)) {
        $errorString .= tr('Invalid `%s` field.', tr('Service name'));
        return false;
    }

    if (!in_array($proto, array('udp', 'tcp', 'tls'))) {
        showBadRequestErrorPage();
    }

    if (!is_number($priority) || $priority > 65535) {
        showBadRequestErrorPage();
    }

    if (!is_number($weight) || $weight > 65535) {
        showBadRequestErrorPage();
    }

    if ($port === '') {
        $errorString .= tr('`%s` field cannot be empty.', tr('Target port'));
        return false;
    }

    if (!is_number($port)) {
        $errorString .= tr('Target port must be a number.');
        return false;
    }

    if ($host === '') {
        $errorString .= tr('`%s` field cannot be empty.', tr('Host'));
        return false;
    }

    if (!isValidDomainName($host)) {
        $errorString .= tr('Invalid `%s` field.', tr('Host'));
        return false;
    }

    return true;
}

/**
 * Validate TTL field for a DNS resource record
 *
 * @param int $ttl TTL value
 * @return int TTL
 */
function client_validate_TTL($ttl)
{
    if (!is_number($ttl) || $ttl < 60 || $ttl > 2147483647) {
        showBadRequestErrorPage();
    }

    return $ttl;
}

/**
 * Create HTML option elements
 *
 * @param array $data Options data
 * @param null|string $value
 * @return string
 */
function client_create_options($data, $value = null)
{
    $options = '';
    reset($data);

    foreach ($data as $item) {
        $options .= '<option value="' . $item . '"' . ($item == $value ? ' selected' : '') . '>' . $item . '</option>';
    }

    return $options;
}

/**
 * Decode DNS record data
 *
 * @param array|null $data DNS record data or NULL
 * @return array
 */
function client_decodeDnsRecordData($data)
{
    $ipv4 = $ipv6 = $srvName = $srvProto = $cname = $txt = $name = $dnsTTL = $srvTargetPort = $srvTargetHost = '';
    $ownedBy = 'custom_dns_feature';

    $srvPrio = 0; // Default priority for SRV records
    $srvWeight = 0; // Default weight for SRV records
    $ttl = 3600; // Default TTL (1 hour)

    if (is_array($data)) {
        # Extract name and ttl field for any record type excepted SRV record
        if ($data['domain_type'] != 'SRV' && preg_match('/^(?P<name>([^\s]+))(?:\s+(?P<ttl>\d+))?/', $data['domain_dns'], $matches)) {
            $name = $matches['name'];
            $ttl = isset($matches['ttl']) ? $matches['ttl'] : $ttl;
        }

        $ownedBy = $data['owned_by'];

        switch ($data['domain_type']) {
            case 'A':
                $ipv4 = $data['domain_text'];
                break;
            case 'AAAA':
                $ipv6 = $data['domain_text'];
                break;
            case 'CNAME':
                $cname = $data['domain_text'];
                break;
            case 'MX':
                # Extract priority and host fields
                if (preg_match('/^(?P<pref>\d+)\s+(?P<host>[^\s]+)/', $data['domain_text'], $matches)) {
                    $srvPrio = $matches['pref'];
                    $srvTargetHost = $matches['host'];
                }
                break;
            case 'SRV':
                # Extract service name, protocol name, owner name and ttl fields
                if (preg_match('/^(?P<srvname>_[^\s.]+)\.(?P<proto>_[^\s.]+)\.(?P<name>[^\s]+)\s+(?P<ttl>\d+)/', $data['domain_dns'], $matches)) {
                    $srvName = $matches['srvname'];
                    $srvProto = $matches['proto'];
                    $name = $matches['name'];
                    $ttl = $matches['ttl'];
                }

                # Extract priority, weight, port and target fields
                if (preg_match('/^(?P<prio>\d+)\s+(?P<weight>\d+)\s(?P<port>\d+)\s+(?P<host>[^\s]+)/', $data['domain_text'], $matches)) {
                    $srvPrio = $matches['prio'];
                    $srvWeight = $matches['weight'];
                    $srvTargetPort = $matches['port'];
                    $srvTargetHost = $matches['host'];
                }
                break;
            default:
                $txt = $data['domain_text'];
        }
    }

    return array(
        $name, $ipv4, $ipv6, $srvName, $srvProto, $ttl, $srvPrio, $srvWeight, $srvTargetPort, $srvTargetHost, $cname,
        $txt, $ownedBy
    );
}

/**
 * Check and save DNS record
 *
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 * @param int $dnsRecordId DNS record unique identifier (0 for new record)
 * @return bool
 */
function client_saveDnsRecord($dnsRecordId)
{
    $mainDmnProps = get_domain_default_props($_SESSION['user_id']);
    $mainDmnId = $mainDmnProps['domain_id'];
    $errorString = '';
    $dnsRecordClass = client_getPost('class');

    if ($dnsRecordId == 0) {
        $dnsRecordType = client_getPost('type');

        if ($dnsRecordClass != 'IN' || !in_array($dnsRecordType, array('A', 'AAAA', 'CNAME', 'MX', 'SPF', 'SRV', 'TXT'))) {
            showBadRequestErrorPage();
        }

        if (client_getPost('domain_id') == 0) {
            $domainName = $mainDmnProps['domain_name'];
            $domainId = 0;
        } else {
            $stmt = exec_query(
                'SELECT alias_id, alias_name FROM domain_aliasses WHERE alias_id = ? AND domain_id = ?',
                array($_POST['domain_id'], $mainDmnId)
            );

            if (!$stmt->rowCount()) {
                showBadRequestErrorPage();
            }

            $row = $stmt->fetchRow();
            $domainName = $row['alias_name'];
            $domainId = $row['alias_id'];
        }
    } else {
        $stmt = exec_query(
            '
                SELECT t1.*, IFNULL(t3.alias_name, t2.domain_name) AS domain_name,
                    IFNULL(t3.alias_status, t2.domain_status) AS domain_status
                FROM domain_dns AS t1
                LEFT JOIN domain AS t2 USING(domain_id)
                LEFT JOIN domain_aliasses AS t3 USING (alias_id)
                WHERE domain_dns_id = ? AND t1.domain_id = ?
            ',
            array($dnsRecordId, $mainDmnId)
        );

        if (!$stmt->rowCount()) {
            showBadRequestErrorPage();
        }

        $row = $stmt->fetchRow();
        $domainId = $row['alias_id'] ? $row['alias_id'] : $row['domain_id'];
        $domainName = $row['domain_name'];
        $dnsRecordType = $row['domain_type'];
    }

    $nameValidationError = '';
    $dnsRecordName = mb_strtolower(client_getPost('dns_name'));
    $ttl = client_validate_TTL(client_getPost('dns_ttl')); // Will show a bad request error page on invalid TTL

    // Substitute @ with domain name
    if ($dnsRecordName == '@') {
        $dnsRecordName = $domainName . '.';
    } // No fully-qualified name, complete it
    elseif ($dnsRecordName != '' && substr($dnsRecordName, -1) !== '.') {
        $dnsRecordName .= '.' . $domainName . '.';
    }

    $dnsRecordName = encode_idna($dnsRecordName);

    # Disallow out-of-zone record
    if ($dnsRecordName !== '' && !preg_match("/(?:.*?\\.)?$domainName\\.$/", $dnsRecordName)) {
        set_page_message(tr('Could not validate DNS resource record: %s', 'out-of-zone data'), 'error');
    } // Remove trailing dot for validation process (will be readded after)
    else {
        $dnsRecordName = rtrim($dnsRecordName, '.');
        if (!client_validate_NAME($dnsRecordName, $dnsRecordType, $nameValidationError)) {
            set_page_message(tr('Could not validate DNS resource record: %s', $nameValidationError), 'error');
        }
    }

    if (Zend_Session::namespaceIsset('pageMessages')) {
        return false;
    }

    switch ($dnsRecordType) {
        case 'A':
            $ip = client_getPost('dns_A_address');
            if (!client_validate_A($ip, $errorString)) {
                set_page_message(tr('Could not validate DNS resource record: %s', $errorString), 'error');
            }

            $dnsRecordData = $ip;
            break;
        case 'AAAA':
            $ip = client_getPost('dns_AAAA_address');
            if (!client_validate_AAAA(client_getPost('dns_AAAA_address'), $errorString)) {
                set_page_message(tr('Could not validate DNS resource record: %s', $errorString), 'error');
            }

            $dnsRecordData = $ip;
            break;
        case 'CNAME':
            $dnsRecordData = mb_strtolower(client_getPost('dns_cname'));
            if ($dnsRecordData == '@') {
                // Substitute @ with domain name
                $dnsRecordData = $domainName;
            } elseif ($dnsRecordData != '' && substr($dnsRecordData, -1) !== '.') {
                // No fully-qualified canonical name, complete it
                $dnsRecordData .= '.' . $domainName;
            }

            $dnsRecordData = encode_idna($dnsRecordData);
            // Remove trailing dot for validation process (will be readded after)
            $dnsRecordData = rtrim($dnsRecordData, '.');

            if (!client_validate_CNAME($dnsRecordData, $errorString)) {
                set_page_message(tr('Could not validate DNS resource record: %s', $errorString), 'error');
            }

            $dnsRecordData .= '.';
            break;
        case'MX':
            $pref = client_getPost('dns_srv_prio');
            $host = mb_strtolower(client_getPost('dns_srv_host'));
            $host = encode_idna($host);

            if ($host != '' && substr($host, -1) !== '.') {
                // No fully-qualified host, complete it
                $host .= '.' . $domainName;
            }

            $host = encode_idna($host);
            // Remove trailing dot for validation process (will be readded after)
            $host = rtrim($host, '.');

            if (!client_validate_MX($pref, $host, $errorString)) {
                set_page_message(tr('Could not validate DNS resource record: %s', $errorString), 'error');
            }

            $dnsRecordData = sprintf('%d %s.', $pref, $host);
            break;
        case 'SRV':
            $srvName = mb_strtolower(client_getPost('dns_srv_name'));
            $srvProto = client_getPost('srv_proto');
            $srvPrio = client_getPost('dns_srv_prio');
            $srvWeight = client_getPost('dns_srv_weight');
            $srvPort = client_getPost('dns_srv_port');
            $srvTarget = mb_strtolower(client_getPost('dns_srv_host'));

            if ($srvTarget == '@') {
                // Substitute @ with domain name
                $srvTarget = $domainName;
            } elseif ($srvTarget != '' && substr($srvTarget, -1) !== '.') {
                // No fully-qualified target host, complete it
                $srvTarget .= '.' . $domainName;
            }

            $srvTarget = encode_idna($srvTarget);
            // Remove trailing dot for validation process (will be readded after)
            $srvTarget = rtrim($srvTarget, '.');

            if (!client_validate_SRV($srvName, $srvProto, $srvPrio, $srvWeight, $srvPort, $srvTarget, $errorString)) {
                set_page_message(tr('Could not validate DNS resource record: %s', $errorString), 'error');
            }

            $dnsRecordName = sprintf('%s._%s.%s', $srvName, $srvProto, $dnsRecordName);
            $dnsRecordData = sprintf('%d %d %d %s.', $srvPrio, $srvWeight, $srvPort, $srvTarget);
            break;
        case 'SPF':
        case 'TXT':
            if (!client_validate_TXT(client_getPost('dns_txt_data'), $errorString)) {
                set_page_message(tr('Could not validate DNS resource record: %s', $errorString), 'error');
            }

            $dnsRecordData = '"' . str_replace('"', '', $_POST['dns_txt_data']) . '"';
            break;
        default :
            showBadRequestErrorPage();
            exit;
    }

    $dnsRecordName .= '.'; // Add trailing dot
    $dnsRecordName .= "\t$ttl"; // Add TTL

    if (Zend_Session::namespaceIsset('pageMessages')) {
        return false;
    }

    $db = iMSCP_Database::getInstance();

    try {
        $db->beginTransaction();

        if (!$dnsRecordId) {
            iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeAddCustomDNSrecord, array(
                'domainId' => $mainDmnId,
                'aliasId' => $domainId,
                'name' => $dnsRecordName,
                'class' => $dnsRecordClass,
                'type' => $dnsRecordType,
                'data' => $dnsRecordData
            ));

            exec_query(
                '
                  INSERT INTO domain_dns (
                    domain_id, alias_id, domain_dns, domain_class, domain_type, domain_text, owned_by, domain_dns_status
                  ) VALUES (
                   ?, ?, ?, ?, ?, ?, ?, ?
                  )
                ',
                array(
                    $mainDmnId, $domainId, $dnsRecordName, $dnsRecordClass, $dnsRecordType, $dnsRecordData,
                    'custom_dns_feature', 'toadd'
                )
            );

            iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterAddCustomDNSrecord, array(
                'id' => $db->insertId(),
                'domainId' => $mainDmnId,
                'aliasId' => $domainId,
                'name' => $dnsRecordName,
                'class' => $dnsRecordClass,
                'type' => $dnsRecordType,
                'data' => $dnsRecordData
            ));
        } else {
            iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeEditCustomDNSrecord, array(
                'id' => $dnsRecordId,
                'domainId' => $mainDmnId,
                'aliasId' => $domainId,
                'name' => $dnsRecordName,
                'class' => $dnsRecordClass,
                'type' => $dnsRecordType,
                'data' => $dnsRecordData
            ));

            exec_query(
                '
                  UPDATE domain_dns
                  SET domain_dns = ?, domain_class = ?, domain_type = ?, domain_text = ?, domain_dns_status = ?
                  WHERE domain_dns_id = ?
                ',
                array($dnsRecordName, $dnsRecordClass, $dnsRecordType, $dnsRecordData, 'tochange', $dnsRecordId)
            );

            // Also update status of any DNS resource record with error
            exec_query(
                "
                  UPDATE domain_dns
                  SET domain_dns_status = 'tochange'
                  WHERE domain_id = ?
                  AND domain_dns_status NOT IN('ok', 'toadd', 'tochange', 'todelete')
                ",
                $mainDmnId
            );

            iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterEditCustomDNSrecord, array(
                'id' => $dnsRecordId,
                'domainId' => $mainDmnId,
                'aliasId' => $domainId,
                'name' => $dnsRecordName,
                'class' => $dnsRecordClass,
                'type' => $dnsRecordType,
                'data' => $dnsRecordData
            ));
        }

        $db->commit();
        send_request();
        write_log(sprintf('DNS resource record has been scheduled for %s by %s', ($dnsRecordId) ? tr('update') : tr('addition'), $_SESSION['user_logged']), E_USER_NOTICE);
    } catch (iMSCP_Exception $e) {
        $db->rollBack();
        if ($e->getCode() == 23000) { // Duplicate entries
            set_page_message(tr('DNS record already exist.'), 'error');
            return false;
        }

        throw $e;
    }

    return true;
}

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $dnsRecordId DNS record unique identifier (0 for new record)
 * @return void
 */
function client_generatePage($tpl, $dnsRecordId)
{
    $cfg = iMSCP_Registry::get('config');
    $mainDomainId = get_user_domain_id($_SESSION['user_id']);

    // Add DNS record
    if ($dnsRecordId == 0) {
        $stmt = exec_query(
            "
                SELECT '0' AS domain_id, domain_name FROM domain WHERE domain_id = ?
                UNION
                SELECT alias_id AS domain_id, alias_name AS domain_name FROM domain_aliasses
                WHERE domain_id = ? AND alias_status <> ?
            ",
            array($mainDomainId, $mainDomainId, 'ordered')
        );

        $domainId = client_getPost('domain_id', '0');
        $selected = $cfg['HTML_SELECTED'];
        $selectOptions = '';

        while ($data = $stmt->fetchRow()) {
            $selectOptions .= '<option value="' . $data['domain_id'] . '"' . ($data['domain_id'] == $domainId ? $selected : '') . '>' . decode_idna($data['domain_name']) . '</option>';
        }

        $tpl->assign(array(
            'SELECT_DOMAINS' => $selectOptions,
            'DNS_TYPE_DISABLED' => ''
        ));
    } // Edit DNS record
    else {
        $stmt = exec_query('SELECT * FROM `domain_dns` WHERE `domain_dns_id` = ? AND `domain_id` = ?', array($dnsRecordId, $mainDomainId));

        if (!$stmt->rowCount()) {
            showBadRequestErrorPage();
        }

        $data = $stmt->fetchRow();
        $tpl->assign(array(
            'ADD_RECORD' => '',
            'DNS_TYPE_DISABLED' => ' disabled'
        ));
    }

    list($name, $ipv4, $ipv6, $srvName, $srvProto, $srvTTL, $srvPriority, $srvWeight, $srvTargetPort, $srvTargetHost,
        $cname, $txt, $ownedBy
        ) = client_decodeDnsRecordData($data);

    // Protection against edition (eg. for external mail MX record)
    if ($ownedBy != 'custom_dns_feature') {
        showBadRequestErrorPage();
    }

    $dnsTypes = client_create_options(array('A', 'AAAA', 'SRV', 'CNAME', 'MX', 'SPF', 'TXT'), client_getPost('type', $data['domain_type']));
    $dnsClasses = client_create_options(array('IN'), client_getPost('class', $data['domain_class']));
    $tpl->assign(array(
        'ID' => tohtml($dnsRecordId),
        'DNS_SRV_NAME' => tohtml(client_getPost('dns_srv_name', decode_idna($srvName))),
        'SELECT_DNS_SRV_PROTOCOL' => client_create_options(array('tcp', 'udp', 'tls'), client_getPost('srv_proto', $srvProto)),
        'DNS_NAME' => tohtml(client_getPost('dns_name', decode_idna(rtrim($name, '.')) . ($name != '' ? '.' : ''))),
        'DNS_TTL' => tohtml(client_getPost('dns_ttl', $srvTTL)),
        'SELECT_DNS_TYPE' => $dnsTypes,
        'SELECT_DNS_CLASS' => $dnsClasses,
        'DNS_ADDRESS' => tohtml(client_getPost('dns_A_address', $ipv4)),
        'DNS_ADDRESS_V6' => tohtml(client_getPost('dns_AAAA_address', $ipv6)),
        'DNS_SRV_PRIO' => tohtml(client_getPost('dns_srv_prio', $srvPriority)),
        'DNS_SRV_WEIGHT' => tohtml(client_getPost('dns_srv_weight', $srvWeight)),
        'DNS_SRV_PORT' => tohtml(client_getPost('dns_srv_port', $srvTargetPort)),
        'DNS_SRV_HOST' => tohtml(client_getPost('dns_srv_host', decode_idna(rtrim($srvTargetHost, '.')) . ($srvTargetHost != '' ? '.' : ''))),
        'DNS_CNAME' => tohtml(client_getPost('dns_cname', decode_idna(rtrim($cname, '.')) . ($cname != '' ? '.' : ''))),
        'DNS_TXT_DATA' => tohtml(client_getPost('dns_txt_data', $txt))
    ));
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');
customerHasFeature('custom_dns_records') or showBadRequestErrorPage();

$dnsRecordId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!empty($_POST)) {
    if (client_saveDnsRecord($dnsRecordId)) {
        if ($dnsRecordId > 0) {
            set_page_message(tr('DNS resource record scheduled for update.'), 'success');
        } else {
            set_page_message(tr('DNS resource record scheduled for addition.'), 'success');
        }

        redirectTo('domains_manage.php');
    }
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'client/dns_edit.tpl',
    'page_message' => 'layout',
    'logged_from' => 'page'
));
$tpl->assign(array(
    'TR_PAGE_TITLE' => ($dnsRecordId > 0) ? tr('Client / Domain / Edit DNS resource record') : tr('Client / Domains / Add DNS resource record'),
    'ACTION_MODE' => ($dnsRecordId > 0) ? 'dns_edit.php?id={ID}' : 'dns_add.php',
    'TR_CUSTOM_DNS_RECORD' => tr('DNS resource record'),
    'TR_DOMAIN' => tr('Domain'),
    'TR_DOMAIN_HELP' => tohtml(tr('Domain for which you want add this DNS resource record.'), 'htmlAttr'),
    'TR_NAME' => tr('Name'),
    'TR_DNS_TYPE' => tr('Type'),
    'TR_DNS_CLASS' => tr('Class'),
    'TR_DNS_NAME' => tr('Name'),
    'TR_DNS_SRV_NAME' => tr('Service name'),
    'TR_DNS_IP_ADDRESS' => tr('IP address'),
    'TR_DNS_IP_ADDRESS_V6' => tr('IPv6 address'),
    'TR_DNS_SRV_PROTOCOL' => tr('Service protocol'),
    'TR_DNS_TTL' => tr('TTL'),
    'TR_DNS_SRV_PRIO' => tr('Priority'),
    'TR_DNS_SRV_WEIGHT' => tr('Relative weight'),
    'TR_DNS_SRV_HOST' => tr('Host'),
    'TR_DNS_SRV_PORT' => tr('Target port'),
    'TR_DNS_CNAME' => tr('Canonical name'),
    'TR_DNS_TXT_DATA' => tr('Data'),
    'TR_ADD' => tr('Add'),
    'TR_SEC' => tr('Sec.'),
    'TR_UPDATE' => tr('Update'),
    'TR_CANCEL' => tr('Cancel')
));

$tpl->assign(($dnsRecordId > 0) ? 'FORM_ADD_MODE' : 'FORM_EDIT_MODE', '');

generateNavigation($tpl);
client_generatePage($tpl, $dnsRecordId);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();

unsetMessages();
