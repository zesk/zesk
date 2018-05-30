#!/bin/bash 
cannon_opts=""

#zesk cannon  --also-match 'extends Object'  'log::' '$this->application->logger->'
#zesk cannon $cannon_opts HTML::cdn_img HTML::img

Color_Off='\033[0m'       # Text Reset
Red='\033[0;31m'          # Red
Blue='\033[0;34m'         # Blue

IBlack='\033[0;90m'       # Black

pause() {
	echo -ne $Blue"Return to continue: "$Color_Off
	read
}

heading() {
	echo -e $Red$*$Color_Off
	echo -ne $IBlack
}

heading "Net_HTTP constants are now ALL CAPS"
zesk cannon Status_Continue STATUS_CONTINUE
zesk cannon Status_Switching_Protocols STATUS_SWITCHING_PROTOCOLS
zesk cannon Status_Processing STATUS_PROCESSING
zesk cannon Status_OK STATUS_OK
zesk cannon Status_Created STATUS_CREATED
zesk cannon Status_Accepted STATUS_ACCEPTED
zesk cannon Status_Non_Authoriative_Information STATUS_NON_AUTHORIATIVE_INFORMATION
zesk cannon Status_No_Content STATUS_NO_CONTENT
zesk cannon Status_Reset_Content STATUS_RESET_CONTENT
zesk cannon Status_Partial_Content STATUS_PARTIAL_CONTENT
zesk cannon Status_Multi_Status STATUS_MULTI_STATUS
zesk cannon Status_Multiple_Choices STATUS_MULTIPLE_CHOICES
zesk cannon Status_Moved_Permanently STATUS_MOVED_PERMANENTLY
zesk cannon Status_Found STATUS_FOUND 
zesk cannon Status_See_Other STATUS_SEE_OTHER
zesk cannon Status_Not_Modified STATUS_NOT_MODIFIED
zesk cannon Status_Use_Proxy STATUS_USE_PROXY 
zesk cannon Status_Temporary_Redirect STATUS_TEMPORARY_REDIRECT
zesk cannon Status_Bad_Request STATUS_BAD_REQUEST 
zesk cannon Status_Unauthorized STATUS_UNAUTHORIZED 
zesk cannon Status_Payment_Granted STATUS_PAYMENT_GRANTED 
zesk cannon Status_Forbidden STATUS_FORBIDDEN 
zesk cannon Status_File_Not_Found STATUS_FILE_NOT_FOUND 
zesk cannon Status_Method_Not_Allowed STATUS_METHOD_NOT_ALLOWED 
zesk cannon Status_Not_Acceptable STATUS_NOT_ACCEPTABLE 
zesk cannon Status_Proxy_Authentication_Required STATUS_PROXY_AUTHENTICATION_REQUIRED
zesk cannon Status_Request_Time_out STATUS_REQUEST_TIMEOUT 
zesk cannon Status_Conflict STATUS_CONFLICT 
zesk cannon Status_Gone STATUS_GONE 
zesk cannon Status_Length_Required STATUS_LENGTH_REQUIRED
zesk cannon Status_Precondition_Failed STATUS_PRECONDITION_FAILED
zesk cannon Status_Request_Entity_Too_Large STATUS_REQUEST_ENTITY_TOO_LARGE
zesk cannon Status_Request_URI_Too_Large STATUS_REQUEST_URI_TOO_LARGE 
zesk cannon Status_Unsupported_Media_Type STATUS_UNSUPPORTED_MEDIA_TYPE 
zesk cannon Status_Requested_range_not_satisfiable STATUS_REQUESTED_RANGE_NOT_SATISFIABLE
zesk cannon Status_Expectation_Failed STATUS_EXPECTATION_FAILED 
zesk cannon Status_Unprocessable_Entity STATUS_UNPROCESSABLE_ENTITY 
zesk cannon Status_Locked STATUS_LOCKED 
zesk cannon Status_Failed_Dependency STATUS_FAILED_DEPENDENCY
zesk cannon Status_Internal_Server_Error STATUS_INTERNAL_SERVER_ERROR 
zesk cannon Status_Not_Implemented STATUS_NOT_IMPLEMENTED 
zesk cannon Status_Overloaded STATUS_OVERLOADED 
zesk cannon Status_Gateway_Timeout STATUS_GATEWAY_TIMEOUT 
zesk cannon Status_HTTP_Version_not_supported STATUS_HTTP_VERSION_NOT_SUPPORTED 
zesk cannon Status_Insufficient_Storage STATUS_INSUFFICIENT_STORAGE 
zesk cannon response_type_info RESPONSE_TYPE_INFO 
zesk cannon response_type_success RESPONSE_TYPE_SUCCESS 
zesk cannon response_type_redirect RESPONSE_TYPE_REDIRECT 
zesk cannon response_type_error_client RESPONSE_TYPE_ERROR_CLIENT 
zesk cannon response_type_error_server RESPONSE_TYPE_ERROR_SERVER 
zesk cannon Method_GET METHOD_GET 
zesk cannon Method_POST METHOD_POST
zesk cannon Method_PUT METHOD_PUT 
zesk cannon Method_DELETE METHOD_DELETE
zesk cannon Method_HEAD METHOD_HEAD 
zesk cannon Method_OPTIONS METHOD_OPTIONS
zesk cannon Method_TRACE METHOD_TRACE 
zesk cannon Method_CONNECT METHOD_CONNECT
zesk cannon request_Referrer REQUEST_REFERRER 
zesk cannon request_User_Agent REQUEST_USER_AGENT 
zesk cannon request_Accept REQUEST_ACCEPT 
zesk cannon request_Content_Type REQUEST_CONTENT_TYPE 
zesk cannon response_Content_Disposition RESPONSE_CONTENT_DISPOSITION 
zesk cannon response_Content_Type RESPONSE_CONTENT_TYPE 
zesk cannon response_Accept_Ranges RESPONSE_ACCEPT_RANGES 
zesk cannon response_Content_Encoding RESPONSE_CONTENT_ENCODING 
zesk cannon response_Transfer_Encoding RESPONSE_TRANSFER_ENCODING 

heading "Module_Picker is now in zesk\\ namespace"
php-find.sh 'Control_Picker'
