<?php
Class KCMeli {
    public function __construct($appId, $secretKey) {
        $this->appId = $appId;
        $this->secretKey = $secretKey;
        $accessData=$this->getAccessData();
        $this->accessToken=$accessData->accessToken;
        $this->userId=$accessData->userId;
    }
    public function curlGet($url,$resjson=true) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $data = curl_exec($ch);
        curl_close($ch);
        if($resjson){
            return json_decode($data);
        }else{
            return $data;
        }
    }
    public function curlPost($url,$data,$isjson=false){
        $ch = curl_init($url);
        if($isjson){
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
                'Content-Type: application/json',                                                                                
                'Content-Length: '.strlen($data))                                                                       
            ); 
        }
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output);
    }
    public function curlPut($url,$data,$isjson=false){
        $ch = curl_init($url);
        if($isjson){
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
                'Content-Type: application/json',                                                                                
                'Content-Length: '.strlen($data))                                                                       
            ); 
        }
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output);
    }
    private function curlDelete($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        $data = curl_exec($ch);
        curl_close($ch);
        return json_decode($data);
    }
    public function getAccessData(){
        $output=new stdClass();
        $DATA_URL='https://api.mercadolibre.com/oauth/token';
        $fields = array(
            'grant_type' => 'client_credentials',
            'client_id' => $this->appId,
            'client_secret' =>  $this->secretKey
        );
        $authData=$this->curlPost($DATA_URL,$fields);
        $output->accessToken=$authData->access_token;
        $output->tokentType=$authData->token_type;
        $output->expiresIn=$authData->expires_in;
        $output->scope=$authData->scope;
        @$output->userId=$authData->x_ml_user_id;
        if(!$output->userId) $output->userId=$authData->user_id;
        $output->refreshToken=$authData->refresh_token;
        return $output;
    }
    
    /*Users ans Apps*/
    public function getUserInfo($user_id){
        $url="https://api.mercadolibre.com/users/$user_id";
        return $this->curlGet($url);
    }
    public function getMyInfo(){
        $user_id=$this->userId;
        $url="https://api.mercadolibre.com/users/$user_id";
        return $this->curlGet($url);
    }
    public function getMyAddress(){
        $user_id=$this->userId;
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/users/$user_id/addresses?access_token=$access_token";
        return $this->curlGet($url);
    }
    public function createTestUser($site_id){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/users/test_user?access_token=$access_token";
        $data='{"site_id":"'.$site_id.'"}';
        return $this->curlPost($url,$data,true);
    }
    
    /*General MELI Info*/
    public function getSites(){
        $url="https://api.mercadolibre.com/sites/";
        return $this->curlGet($url);
    }
    public function getSite($code){
        $url="https://api.mercadolibre.com/sites/$code";
        return $this->curlGet($url);
    }
    public function getCurrencies(){
        $url="https://api.mercadolibre.com/currencies";
        return $this->curlGet($url);
    }
    public function getCurrency($code){
        $url="https://api.mercadolibre.com/currencies/$code";
        return $this->curlGet($url);
    }
    public function getCategories($code){
        $url="https://api.mercadolibre.com/sites/$code/categories";
        return $this->curlGet($url);
    }
    public function getCategory($code){
        $url="https://api.mercadolibre.com/categories/$code";
        return $this->curlGet($url);
    }
    public function getSubCategories($code){
        $url="https://api.mercadolibre.com/categories/$code";
        $cat=$this->curlGet($url);
        $children=null;
        if($cat->children_categories){
            $children=$cat->children_categories;
        }
        return $children;
    }
    public function getCategoryAttr($code){
        $url="https://api.mercadolibre.com/categories/$code/attributes";
        return $this->curlGet($url);
    }
    
    /*Listings*/
    public function getProductIDs($limit=50,$offset=0,$filter=null){
        $user_id=$this->userId;
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/users/$user_id/items/search?access_token=$access_token&limit=$limit&offset=$offset";
        if($filter) $url.='&'.$filter;
        return $this->curlGet($url);
    }
    
    public function getProduct($id,$echourl=false){
        $url="https://api.mercadolibre.com/items/$id";
        if($echourl) print_r($url);
        return $this->curlGet($url);
    }
    //Get always the last version of the item's ID
    //using any ID that it could had in the past
    //If a present ID is used, it'll return the same one
    public function getProductEdgeID($id){
        $item=$this->getProduct($id);
        if($item->error){
            return false;
        }else{
            $url=$item->permalink;
            $sellerid=$item->seller_id;
            $ch=curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response=curl_exec($ch);
            curl_close($ch);
            $header="Location: ";
            $pos=strpos($response,$header);
            $pos+=strlen($header);
            $redirect_url=substr($response,$pos,strpos($response,"\r\n",$pos)-$pos);
            $url=$redirect_url;
            $sliced_id=array();
            preg_match("/\/ML.*-\d*-/",$url,$sliced_id);
            $nwid='';
            foreach($sliced_id as $sid){
				$nwid=ltrim($sid,'/');
				$nwid=str_replace('-','',$nwid);
			}
            if($nwid){
                $newitem=$this->getProduct($nwid);
                if($sellerid==$newitem->seller_id){
                    return $nwid;
                }else{
                    return $id;
                }
            }else{
                return $id;
            }
        }
    }
    
    public function getProductDescription($id){
        $url="https://api.mercadolibre.com/items/$id/description";
        return $this->curlGet($url);
    }
    public function validateProduct($data){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/items/validate?access_token=$access_token";
        return $this->curlPost($url,$data,true);
    }
    public function listProduct($data){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/items?access_token=$access_token";
        return $this->curlPost($url,$data,true);
    }
    public function updateProduct($item_id,$data){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/items/$item_id?access_token=$access_token";
        return $this->curlPut($url,$data,true);
    }
    public function updateProductDescription($item_id,$data){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/items/$item_id/description?access_token=$access_token";
        return $this->curlPut($url,$data,true);
    }
    public function changeStatusProduct($item_id,$status){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/items/$item_id?access_token=$access_token";
        $data='{"status":"'.$status.'"}';
        return $this->curlPut($url,$data,true);
    }
    public function activateProduct($item_id){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/items/$item_id?access_token=$access_token";
        $data='{"status":"active"}';
        return $this->curlPut($url,$data,true);
    }
    public function pauseProduct($item_id){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/items/$item_id?access_token=$access_token";
        $data='{"status":"paused"}';
        return $this->curlPut($url,$data,true);
    }
    public function closeProduct($item_id){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/items/$item_id?access_token=$access_token";
        $data='{"status":"closed"}';
        return $this->curlPut($url,$data,true);
    }
    public function deleteProduct($item_id){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/items/$item_id?access_token=$access_token";
        $data='{"deleted":"true"}';
        return $this->curlPut($url,$data,true);
    }
    public function relistProduct($item_id,$data){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/items/$item_id/relist?access_token=$access_token";
        return $this->curlPost($url,$data,true);
    }
    
    /*Orders*/
    public function getSellerOrders($limit=50,$offset=0,$sort="&sort=date_desc"){
        $user_id=$this->userId;
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/orders/search?seller=$user_id&access_token=$access_token&limit=$limit&offset=$offset".$sort;
        return $this->curlGet($url);
    }
    public function getSellerOrder($order_id, $echo=false){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/orders/$order_id?access_token=$access_token";
        if($echo) print_r($url);
        return $this->curlGet($url);
    }
    public function getSellerOrdersArchived($limit=50,$offset=0,$sort="&sort=date_desc",$echourl=false){
        $user_id=$this->userId;
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/orders/search/archived?seller=$user_id&access_token=$access_token&limit=$limit&offset=$offset".$sort;
        if($echourl) print_r($url);
        return $this->curlGet($url);
    }
    public function getSellerOrdersRecent($limit=50,$offset=0,$sort="&sort=date_desc"){
        $user_id=$this->userId;
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/orders/search/recent?seller=$user_id&access_token=$access_token&limit=$limit&offset=$offset".$sort;
        return $this->curlGet($url);
    }
    public function getSellerOrdersPending($limit=50,$offset=0,$sort="&sort=date_desc"){
        $user_id=$this->userId;
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/orders/search/pending?seller=$user_id&access_token=$access_token&limit=$limit&offset=$offset".$sort;
        return $this->curlGet($url);
    }
    public function getBuyerOrders(){
        $user_id=$this->userId;
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/orders/search?buyer=$user_id&access_token=$access_token";
        return $this->curlGet($url);
    }
    public function getOrderNotes($order_id){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/orders/$order_id/notes?access_token=$access_token";
        return $this->curlGet($url);
    }
    public function setOrderNote($order_id,$text_note){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/orders/$order_id/notes?access_token=$access_token";
        $data='{"note":"'.$text_note.'"}';
        return $this->curlPost($url,$data,true);
    }
    public function updateOrderNote($order_id,$note_id,$text_note){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/orders/$order_id/notes/$note_id?access_token=$access_token";
        $data='{"note":"'.$text_note.'"}';
        return $this->curlPut($url,$data,true);
    }
    public function deleteOrderNote($order_id,$note_id){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/orders/$order_id/notes/$note_id?access_token=$access_token";
        return $this->curlDelete($url);
    }
    
    /*Payments*/
    public function getPaymentInfo($payment_id){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/collections/$payment_id?access_token=$access_token";
        return $this->curlGet($url);
    }
    public function getOperationInfo($payment_id){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/collections/$payment_id?access_token=$access_token";
        return $this->curlGet($url);
    }
    
    /*Shipping*/
    public function getShippingMethods($site_id){
        $url="https://api.mercadolibre.com/sites/$site_id/shipping_methods";
        return $this->curlGet($url);
    }
    public function getOrderShipment($order_id){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/orders/$order_id/shipments?access_token=$access_token";
        return $this->curlGet($url);
    }
    public function getShipmentInfo($shipment_id){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/shipments/$shipment_id?access_token=$access_token";
        return $this->curlGet($url);
    }
    
    /*Questions*/
    public function getProductQuestions($item_id){
        $url="https://api.mercadolibre.com/questions/search?item_id=$item_id";
        return $this->curlGet($url);
    }
    public function getQuestion($question_id){
        $access_token=$this->accessToken;
        $url="https://api.mercadolibre.com/questions/$question_id?access_token=$access_token";
        return $this->curlGet($url);
    }
}
?>
