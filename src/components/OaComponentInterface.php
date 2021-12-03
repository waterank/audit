<?php

namespace waterank\audit\components;

interface OaComponentInterface
{
    public function createOa($params,$auditType,$accesToken);
    
    public function getAccessToken($userId,$oaRefreshToken);
    
    public function getOaRedirectUrl($params);
    
    public function getRefreshToken($code);
    
    public function getClientToken();
    
    public function getOaNodeInfo($accessToken,$entry_ids);
    
    public function getBusinessLogicClass($auditType);
    
    public function getAuditTypeList();
}
