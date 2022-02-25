<?php

namespace waterank\audit\components;

interface OaComponentInterface
{
    public function createOa($params, $auditType, $accessToken);

    public function createBulkOa($params, $auditType, $accessToken, $customConfig = []);

    public function getAccessToken($userId, $oaRefreshToken);

    public function getOaRedirectUrl($cacheKey);

    public function getRefreshToken($code);

    public function getClientToken();

    public function getOaNodeInfo($accessToken, $entry_ids);

    public function getBusinessLogicClass($auditType);

    public function getAuditTypeList();
}
