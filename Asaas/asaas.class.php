<?php

 /**
  * asaas
  */
 class asaas extends Conn{

   /**
    * @var string
    */
   private $access_token;

   /**
    * @var string
    */
   private $addressKey;

   /**
    * @var float
    */
   public $unit_price;

   /**
    * @var float
    */
   public $discount;

   /**
    * @var float
    */
   public $amount;

   /**
    * @var int
    */
   public $amount_cents;

   /**
    * @var string
    */
   public $invoice_ref;

   /**
    * @var string
    */
   public $title;

   /**
    * @var string
    */
   public $site;

   /**
    * @var string
    */
   public $method;

   /**
    * @var string
    */
   public $link;

   /**
    * @var string
    */
   public $pixcode;

   /**
    * @var string
    */
   public $qrcodepix;

   /**
    * @var string
    */
   public $boleto;

   /**
    * @var boolean
    */
   public $error = false;

   /**
    * @var string
    */
   public $message_erro;

   /**
    * @var object
    */
   public $payer;

   /**
    * @var object
    */
   public $seller;




   public function __construct($dados_api){
      
      parent::__construct();
      $this->conn      = new Conn;
      $this->pdo       = $this->conn->pdo();

     if(json_decode($dados_api)){

       try {

         $dados = json_decode($dados_api);

         if(isset($dados->access_token, $dados->addressKey)){

            $this->access_token = $dados->access_token;
            $this->addressKey   = $dados->addressKey;

         }else{
           $this->error        = true;
           $this->message_erro = "not found access token";
           return false;
         }

       } catch (\Exception $e) {
         $this->error        = true;
         $this->message_erro = "not found access token";
         return false;
       }

     }else{
       $this->error        = true;
       $this->message_erro = "not found access token";
       return false;
     }

   }
   
   public function setExtraInfo($extra){
       
          $query = $this->pdo->prepare("UPDATE `invoices` SET extra_info= :extra_info WHERE ref=:ref");
          $query->bindValue(':extra_info', $extra);
          $query->bindValue(':ref', $this->invoice_ref);

          if($query->execute()){
            return true;
          }else{
            return false;
          }
          
   }

   public function save(){

     if($this->method == "credit_card"){

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://www.asaas.com/api/v3/paymentLinks',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
          "name": "'.$this->title.'",
          "description": "'.$this->invoice_ref.'",
          "value": '.$this->amount.',
          "billingType": "CREDIT_CARD",
          "chargeType": "DETACHED",
          "maxInstallmentCount": 1,
          "notificationEnabled": true
        }',
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'access_token: '.$this->access_token
          ),
        ));

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        curl_close($curl);

        // Log da resposta para debug
        error_log("Asaas Credit Card Response (HTTP $http_code): " . $response);

      try {

        if ($response === false) {
            throw new Exception('Erro cURL: ' . $curl_error);
        }

        if(json_decode($response)){

          $response = json_decode($response);

          if(isset($response->id)){
              
            self::setExtraInfo($response->id);

            $this->link = $response->url;
            error_log("Link de pagamento Asaas gerado com sucesso - ID: " . $response->id . " - Ref: " . $this->invoice_ref);
            return true;

          }else{
            $this->error        = true;
            $this->message_erro = isset($response->errors) ? $response->errors[0]->description : "Desculpe, tente mais tarde";
            error_log("Erro Cartão Asaas - Sem ID na resposta: " . json_encode($response));
            return false;
          }

        }else{
          $this->error        = true;
          $this->message_erro = "Resposta inválida da API Asaas";
          error_log("Erro Cartão Asaas - Resposta não é JSON válido: " . $response);
          return false;
        }

      } catch (Throwable $e) {
        $this->error        = true;
        $this->message_erro = $e->getMessage();
        return false;
      }

     }else if($this->method == "pix"){

       // Validações antes de fazer a requisição
       if(empty($this->addressKey)) {
           $this->error = true;
           $this->message_erro = "Chave PIX não configurada";
           error_log("Erro PIX Asaas - Chave PIX não configurada para cliente");
           return false;
       }

       if(empty($this->access_token)) {
           $this->error = true;
           $this->message_erro = "Token de acesso não configurado";
           error_log("Erro PIX Asaas - Token de acesso não configurado para cliente");
           return false;
       }

       $curl = curl_init();

       curl_setopt_array($curl, array(
         CURLOPT_URL => 'https://www.asaas.com/api/v3/pix/qrCodes/static',
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => '',
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 30,
         CURLOPT_FOLLOWLOCATION => true,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_CUSTOMREQUEST => 'POST',
         CURLOPT_POSTFIELDS =>'
         {
         "addressKey": "'.$this->addressKey.'",
         "description": "'.$this->invoice_ref.'",
         "value": '.$this->amount.'
        }',
         CURLOPT_HTTPHEADER => array(
           'Content-Type: application/json',
           'access_token: '.$this->access_token
         ),
       ));

       $response = curl_exec($curl);
       $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
       $curl_error = curl_error($curl);
       curl_close($curl);

       // Log da resposta para debug
       error_log("Asaas PIX Response (HTTP $http_code): " . $response);

       try {

         if ($response === false) {
             throw new Exception('Erro cURL: ' . $curl_error);
         }

         if(json_decode($response)){

           $response = json_decode($response);

           if(isset($response->id)){
               
             self::setExtraInfo($response->id);

             $this->pixcode   = $response->payload;
             $this->qrcodepix = "data:image/jpeg;base64,{$response->encodedImage}";
             
             error_log("PIX Asaas gerado com sucesso - ID: " . $response->id . " - Ref: " . $this->invoice_ref);
             return true;

           }else{
             $this->error        = true;
             $this->message_erro = isset($response->errors) ? $response->errors[0]->description : 'Desculpe, tente mais tarde';
             error_log("Erro PIX Asaas - Sem ID na resposta: " . $response);
             return false;
           }

         }else{
           $this->error        = true;
           $this->message_erro = 'Resposta inválida da API Asaas';
           error_log("Erro PIX Asaas - Resposta não é JSON válido: " . $response);
           return false;
         }

       } catch (Throwable $e) {
         $this->error        = true;
         $this->message_erro = $e->getMessage();
         return false;
       }

     }else if($this->method == "boleto"){

         $curl = curl_init();

         curl_setopt_array($curl, array(
           CURLOPT_URL => 'https://www.asaas.com/api/v3/paymentLinks',
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_ENCODING => '',
           CURLOPT_MAXREDIRS => 10,
           CURLOPT_TIMEOUT => 0,
           CURLOPT_FOLLOWLOCATION => true,
           CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
           CURLOPT_CUSTOMREQUEST => 'POST',
           CURLOPT_POSTFIELDS =>'{
           "name": "'.$this->title.'",
           "description": "'.$this->invoice_ref.'",
           "value": '.$this->amount.',
           "billingType": "BOLETO",
           "chargeType": "DETACHED",
           "dueDateLimitDays": 5,
           "maxInstallmentCount": 1,
           "notificationEnabled": true
         }',
           CURLOPT_HTTPHEADER => array(
             'Content-Type: application/json',
             'access_token: '.$this->access_token
           ),
         ));

         $response = curl_exec($curl);
         $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
         $curl_error = curl_error($curl);
         curl_close($curl);

         // Log da resposta para debug
         error_log("Asaas Boleto Response (HTTP $http_code): " . $response);

       try {

         if ($response === false) {
             throw new Exception('Erro cURL: ' . $curl_error);
         }

         if(json_decode($response)){

           $response = json_decode($response);

           if(isset($response->id)){
               
             self::setExtraInfo($response->id);

             $this->boleto = $response->url;
             error_log("Boleto Asaas gerado com sucesso - ID: " . $response->id . " - Ref: " . $this->invoice_ref);
             return true;

           }else{
             $this->error        = true;
             $this->message_erro = isset($response->errors) ? $response->errors[0]->description : "Desculpe, tente mais tarde";
             error_log("Erro Boleto Asaas - Sem ID na resposta: " . json_encode($response));
             return false;
           }

         }else{
           $this->error        = true;
           $this->message_erro = "Resposta inválida da API Asaas";
           error_log("Erro Boleto Asaas - Resposta não é JSON válido: " . $response);
           return false;
         }

       } catch (Throwable $e) {
         $this->error        = true;
         $this->message_erro = $e->getMessage();
         return false;
       }

     }

   }

 }
