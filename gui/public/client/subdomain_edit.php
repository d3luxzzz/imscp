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

use iMSCP\VirtualFileSystem as VirtualFileSystem;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get subdomain data
 *
 * @access private
 * @param int $subdomainId Subdomain unique identifier
 * @param string $subdomainType Subdomain Type
 * @return array|bool Subdomain data or FALSE on error
 */
function _client_getSubdomainData($subdomainId, $subdomainType)
{
    static $subdomainData = null;

    if (null !== $subdomainData) {
        return $subdomainData;
    }

    $mainDmnProps = get_domain_default_props($_SESSION['user_id']);
    $domainId = $mainDmnProps['domain_id'];
    $domainName = $mainDmnProps['domain_name'];

    if ($subdomainType == 'dmn') {
        $query = '
            SELECT subdomain_name , subdomain_mount AS subdomain_mount, subdomain_document_root AS document_root,
              subdomain_url_forward AS forward_url, subdomain_type_forward AS type_forward,
              subdomain_host_forward AS host_forward
            FROM subdomain
            WHERE subdomain_id = ? AND domain_id = ? AND subdomain_status = ?
        ';
    } else {
        $query = '
            SELECT t1.subdomain_alias_name AS subdomain_name, t1.subdomain_alias_mount AS subdomain_mount,
              t1.subdomain_alias_document_root AS document_root, t1.subdomain_alias_url_forward AS forward_url,
              t1.subdomain_alias_type_forward AS type_forward, t1.subdomain_alias_host_forward AS host_forward,
              t2.alias_name AS alias_name
            FROM subdomain_alias AS t1 INNER JOIN domain_aliasses AS t2 USING(alias_id)
            WHERE subdomain_alias_id = ? AND t2.domain_id = ? AND t1.subdomain_alias_status = ?
        ';
    }

    $stmt = exec_query($query, array($subdomainId, $domainId, 'ok'));
    if (!$stmt->rowCount()) {
        return false;
    }

    $subdomainData = $stmt->fetchRow();

    if ($subdomainType == 'dmn') {
        $subdomainData['subdomain_name'] .= '.' . $domainName;
        $subdomainData['subdomain_name_utf8'] = decode_idna($subdomainData['subdomain_name']);
    } else {
        $subdomainData['subdomain_name'] .= '.' . $subdomainData['alias_name'];
        $subdomainData['subdomain_name_utf8'] = decode_idna($subdomainData['subdomain_name']);
    }

    return $subdomainData;
}

/**
 * Generate page
 *
 * @param $tpl iMSCP_pTemplate
 * @return void
 */
function client_generatePage($tpl)
{
    if (!isset($_GET['id']) || !isset($_GET['type']) || !($_GET['type'] == 'dmn' || $_GET['type'] == 'als')) {
        showBadRequestErrorPage();
    }

    $subdomainId = intval($_GET['id']);
    $subdomainType = clean_input($_GET['type']);
    $subdomainData = _client_getSubdomainData($subdomainId, $subdomainType);
    if ($subdomainData === false) {
        showBadRequestErrorPage();
    }

    $forwardHost = 'Off';

    if (empty($_POST)) {
        $documentRoot = isset($subdomainData['document_root']) ? $subdomainData['document_root'] : '/';
        $documentRoot = substr($documentRoot, 7);

        if ($subdomainData['forward_url'] != 'no') {
            $urlForwarding = true;
            $uri = iMSCP_Uri_Redirect::fromString($subdomainData['forward_url']);
            $forwardUrlScheme = $uri->getScheme() . '://';
            $forwardUrl = substr($uri->getUri(), strlen($forwardUrlScheme));
            $forwardType = $subdomainData['type_forward'];
            $forwardHost = $subdomainData['host_forward'];
        } else {
            $urlForwarding = false;
            $forwardUrlScheme = 'http://';
            $forwardUrl = '';
            $forwardType = '302';
        }
    } else {
        $documentRoot = isset($_POST['document_root']) ? $_POST['document_root'] : '/';
        $urlForwarding = (isset($_POST['url_forwarding']) && $_POST['url_forwarding'] == 'yes') ? true : false;
        $forwardUrlScheme = isset($_POST['forward_url_scheme']) ? $_POST['forward_url_scheme'] : 'http://';
        $forwardUrl = isset($_POST['forward_url']) ? $_POST['forward_url'] : '';
        $forwardType = (isset($_POST['forward_type']) && in_array($_POST['forward_type'], array('301', '302', '303', '307', 'proxy'), true)) ? $_POST['forward_type'] : '302';

        if ($forwardType == 'proxy' && isset($_POST['forward_host'])) {
            $forwardHost = 'On';
        }
    }

    # Set parameters for the FTP chooser
    $_SESSION['vftp_root_dir'] = $subdomainData['subdomain_mount'] . '/htdocs';
    $_SESSION['vftp_hidden_dirs'] = array();
    $_SESSION['vftp_unselectable_dirs'] = array();

    $tpl->assign(array(
        'SUBDOMAIN_ID' => $subdomainId,
        'SUBDOMAIN_TYPE' => $subdomainType,
        'SUBDOMAIN_NAME' => tohtml($subdomainData['subdomain_name_utf8']),
        'DOCUMENT_ROOT' => tohtml($documentRoot),
        'FORWARD_URL_YES' => ($urlForwarding) ? ' checked' : '',
        'FORWARD_URL_NO' => ($urlForwarding) ? '' : ' checked',
        'HTTP_YES' => ($forwardUrlScheme == 'http://') ? ' selected' : '',
        'HTTPS_YES' => ($forwardUrlScheme == 'https://') ? ' selected' : '',
        'FORWARD_URL' => $forwardUrl !== '' ? tohtml(decode_idna($forwardUrl)) : '',
        'FORWARD_TYPE_301' => ($forwardType == '301') ? ' checked' : '',
        'FORWARD_TYPE_302' => ($forwardType == '302') ? ' checked' : '',
        'FORWARD_TYPE_303' => ($forwardType == '303') ? ' checked' : '',
        'FORWARD_TYPE_307' => ($forwardType == '307') ? ' checked' : '',
        'FORWARD_TYPE_PROXY' => ($forwardType == 'proxy') ? ' checked' : '',
        'FORWARD_HOST' => ($forwardHost == 'On') ? ' checked' : ''
    ));

    // Cover the case where the subdomain is currently redirected to another domain
    // In such a case the customer must first disable the redirection, and edit the subdomain again to set an
    // alternative DocumentRoot
    $vfs = new VirtualFileSystem($_SESSION['user_logged'], $subdomainData['subdomain_mount']);
    if(!$vfs->exists('/htdocs', VirtualFileSystem::VFS_TYPE_DIR)) {
        $tpl->assign('DOCUMENT_ROOT_BLOC', '');
    }
}

/**
 * Edit subdomain
 *
 * @return bool TRUE on success, FALSE on failure
 */
function client_editSubdomain()
{
    if (!isset($_GET['id']) || !isset($_GET['type']) || !($_GET['type'] == 'dmn' || $_GET['type'] == 'als')) {
        showBadRequestErrorPage();
    }

    $subdomainId = clean_input($_GET['id']);
    $subdomainType = clean_input($_GET['type']);
    $subdomainData = _client_getSubdomainData($subdomainId, $subdomainType);

    if ($subdomainData === false) {
        showBadRequestErrorPage();
    }

    if(isset($_POST['document_root'])) {
        $documentRoot = rtrim(utils_normalizePath(clean_input($_POST['document_root'])), '/');

        if($documentRoot != '') {
            $vfs = new VirtualFileSystem($_SESSION['user_logged'], $subdomainData['subdomain_mount'] . '/htdocs');
            if(!$vfs->exists($documentRoot, VirtualFileSystem::VFS_TYPE_DIR)) {
                set_page_message(tr('The new document root must pre-exists inside the /htdocs directory.'), 'error');
                return false;
            }
        }

        $documentRoot = '/htdocs' . $documentRoot;
    } else {
        $documentRoot = '/htdocs';
    }

    // Check for URL forwarding option
    $forwardUrl = 'no';
    $forwardType = null;
    $forwardHost = 'Off';
    if (isset($_POST['url_forwarding']) && $_POST['url_forwarding'] == 'yes' &&
        isset($_POST['forward_type']) && in_array($_POST['forward_type'], array('301', '302', '303', '307', 'proxy'), true)
    ) {
        if (!isset($_POST['forward_url_scheme']) || !isset($_POST['forward_url'])) {
            showBadRequestErrorPage();
        }

        $forwardUrl = clean_input($_POST['forward_url_scheme']) . clean_input($_POST['forward_url']);
        $forwardType = clean_input($_POST['forward_type']);

        if ($forwardType == 'proxy' && isset($_POST['forward_host'])) {
            $forwardHost = 'On';
        }

        try {
            try {
                $uri = iMSCP_Uri_Redirect::fromString($forwardUrl);
            } catch (Zend_Uri_Exception $e) {
                throw new iMSCP_Exception(tr('Forward URL %s is not valid.', "<strong>$forwardUrl</strong>"));
            }

            $uri->setHost(encode_idna(mb_strtolower($uri->getHost())));
            $uriPath = rtrim(preg_replace('#/+#', '/', $uri->getPath()), '/') . '/'; // normalize path
            $uri->setPath($uriPath);

            if ($uri->getHost() == $subdomainData['subdomain_name'] && $uri->getPath() == '/') {
                throw new iMSCP_Exception(
                    tr('Forward URL %s is not valid.', "<strong>$forwardUrl</strong>") . ' ' .
                    tr(
                        'Subdomain %s cannot be forwarded on itself.',
                        "<strong>{$subdomainData['subdomain_name_utf8']}</strong>"
                    )
                );
            }

            if ($forwardType == 'proxy') {
                $port = $uri->getPort();
                if ($port && $port < 1025) {
                    throw new iMSCP_Exception(tr('Unallowed port in forward URL. Only ports above 1024 are allowed.', 'error'));
                }
            }

            $forwardUrl = $uri->getUri();
        } catch (Exception $e) {
            set_page_message($e->getMessage(), 'error');
            return false;
        }
    }

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeEditSubdomain, array(
        'subdomainId' => $subdomainId,
        'documentRoot' => $documentRoot,
        'forwardUrl' => $forwardUrl,
        'forwardType' => $forwardType,
        'forwardHost' => $forwardHost
    ));

    if ($subdomainType == 'dmn') {
        $query = '
            UPDATE subdomain
            SET subdomain_document_root = ?, subdomain_url_forward = ?, subdomain_type_forward = ?,
              subdomain_host_forward = ?, subdomain_status = ?
            WHERE subdomain_id = ?
        ';
    } else {
        $query = '
            UPDATE subdomain_alias
            SET subdomain_alias_document_root = ?, subdomain_alias_url_forward = ?, subdomain_alias_type_forward = ?,
              subdomain_alias_host_forward = ?, subdomain_alias_status = ?
            WHERE subdomain_alias_id = ?
        ';
    }

    exec_query($query, array($documentRoot, $forwardUrl, $forwardType, $forwardHost, 'tochange', $subdomainId));

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterEditSubdomain, array(
        'subdomainId' => $subdomainId,
        'documentRoot' => $documentRoot,
        'forwardUrl' => $forwardUrl,
        'forwardType' => $forwardType,
        'forwardHost' => $forwardHost
    ));

    send_request();
    write_log(sprintf('%s updated properties of the Ms subdomain', $_SESSION['user_logged'], $subdomainData['subdomain_name_utf8']), E_USER_NOTICE);
    return true;
}

/***********************************************************************************************************************
 * Main
 */


require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');
customerHasFeature('subdomains') or showBadRequestErrorPage();

if (!empty($_POST) && client_editSubdomain()) {
    set_page_message(tr('Subdomain successfully scheduled for update'), 'success');
    redirectTo('domains_manage.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'client/subdomain_edit.tpl',
    'page_message' => 'layout',
    'document_root_bloc' => 'page'
));
$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('Client / Domains / Edit Subdomain'),
    'TR_SUBDOMAIN' => tr('Subdomain'),
    'TR_SUBDOMAIN_NAME' => tr('Subdomain name'),
    'TR_DOCUMENT_ROOT' => tr('Document root'),
    'TR_DOCUMENT_ROOT_TOOLTIP' => tr("You can set an alternative document root. This is mostly needed when using a PHP framework such as Symfony. Note that the new document root will live inside the default  `/htdocs' document root. Be aware that the directory for the new document root must pre-exist."),
    'TR_CHOOSE_DIR' => tr('Choose dir'),
    'TR_URL_FORWARDING' => tr('URL forwarding'),
    'TR_FORWARD_TO_URL' => tr('Forward to URL'),
    'TR_URL_FORWARDING_TOOLTIP' => tr('Allows to forward any request made to this domain to a specific URL.'),
    'TR_YES' => tr('Yes'),
    'TR_NO' => tr('No'),
    'TR_HTTP' => 'http://',
    'TR_HTTPS' => 'https://',
    'TR_FORWARD_TYPE' => tr('Forward type'),
    'TR_301' => '301',
    'TR_302' => '302',
    'TR_303' => '303',
    'TR_307' => '307',
    'TR_PROXY' => 'PROXY',
    'TR_PROXY_PRESERVE_HOST' => tr('Preserve Host'),
    'TR_UPDATE' => tr('Update'),
    'TR_CANCEL' => tr('Cancel')
));

iMSCP_Events_Aggregator::getInstance()->registerListener('onGetJsTranslations', function ($e) {
    /** @var $e iMSCP_Events_Event */
    $translations = $e->getParam('translations');
    $translations['core']['close'] = tr('Close');
    $translations['core']['ftp_directories'] = tr('Select your own document root');
});

generateNavigation($tpl);
client_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
