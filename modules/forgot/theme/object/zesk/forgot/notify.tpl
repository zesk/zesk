<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/forgot/theme/object/zesk/forgot/notify.tpl $
 * @author $Author: kent $
 * @package {package}
 * @subpackage {subpackage}
 * @copyright Copyright (C) 2016, {company}. All rights reserved.
 */
echo "To: {user_email}\n";
echo "From: \"{from_name}\" <{from_email}>\n";
?>Subject: {subject}

You recently requested a forgotten password at

	{url_scheme}://{url_host}/{url_path}

In order to reset your password, please click the following link:

	{url_scheme}://{url_host}/forgot/validate/{forgot_code}

If you did not request this email, please notify a site administrator.
