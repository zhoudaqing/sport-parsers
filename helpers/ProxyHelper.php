<?phpnamespace parsersPicksgrail\helpers;class ProxyHelper {    protected $config;    protected $domain;    protected $attempts = 10;    public function __construct($config, $domain) {        $this->config = $config;        $this->domain = $domain;    }    public function getProxy($cookies, $headers, $domain) {        for ($i = 0; $i < $this->attempts; $i++) {            $proxyArray = $this->getOneProxyFromList();            $result = $this->checkProxy($proxyArray, $cookies, $headers, $domain);            if ($result === true) {                return $proxyArray;            }        }        return false;    }    public function getOneProxyFromList() {        //получение списка прокси        $arrayProxies = file(MAIN_DIR.'/'.$this->config['proxy_file']);        //чистка и получаем случайный прокси из списка        $cleanOneProxy = trim($arrayProxies[rand(0, (count($arrayProxies))-1)]);        //разделение на протокол, айпи, порт        $resultProxy = explode(':', $cleanOneProxy);        //чистка от грязи        $resultProxy[1] = str_replace('//', '', $resultProxy[1]);        return $resultProxy;    }    public function checkProxy($proxy, $cookies, $headers, $domain) {        dump($proxy);        /*        $ch = curl_init($domain);        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);        curl_setopt($ch, CURLOPT_HEADER, 0);        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);        curl_setopt($ch, CURLOPT_COOKIEFILE, "");        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);        curl_setopt($ch, CURLOPT_TIMEOUT, 15);        $html = curl_exec($ch);        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);        curl_close($ch);        dump($httpCode);        die;        */        $url = $domain;        $ch = curl_init($url);        curl_setopt($ch, CURLOPT_URL, $url);        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        curl_setopt($ch, CURLOPT_PROXYPORT, $proxy[2]);        curl_setopt($ch, CURLOPT_PROXY, $proxy[1]);        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);        curl_setopt($ch, CURLOPT_HEADER, 1);        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);        curl_setopt($ch, CURLOPT_TIMEOUT, 15);        /*        //>если есть логин и пароль к прокси        if (!empty($this->login) && !empty($this->password) && $this->proxyType === 'SOCKS5') {            $loginWithPassword = $this->login . ":" . $this->password;            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $loginWithPassword);            curl_setopt($ch, CURLOPT_PROXYTYPE, 'SOCKS5');        }        //<        */        $html = curl_exec($ch);        //получаем код ответа сервера        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);        //закрываем сессию        curl_close($ch);        echo "Response from server - " . $httpCode . "\n";        die;        if($httpCode === 200) {            return true;        } else {            return false;        }    }    public function getHtmlContentFromUrlWithProxy($parseUrl, $cookies, $headers, $domain) {        $ch = curl_init($parseUrl);        curl_setopt($ch, CURLOPT_HEADER, false);        //получение одного прокси        $proxyArray = $this->getProxy($cookies, $headers, $domain);        $proxy = '';        //>проверки на добавление других настроек        if($proxyArray !== false) {            $proxy = implode(':',$proxyArray);            curl_setopt($ch, CURLOPT_PROXY, $proxyArray[0]);            curl_setopt($ch, CURLOPT_PROXYPORT, $proxyArray[1]);        }        //для всех досок, кроме olx будут использоваться эти настройки        if ($domain !== 'olx.by') {            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);        }        //>если есть логин и пароль к прокси// не предусматривать эту проверку        if (!empty($this->login) && !empty($this->password) && $this->proxyType === 'CURLPROXY_SOCKS5') {            $loginWithPassword = $this->login . ":" . $this->password;            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $loginWithPassword);        }        //<        curl_setopt($ch, CURLOPT_PROXYTYPE, $this->proxyType);        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);        curl_setopt($ch, CURLOPT_TIMEOUT, 15);        curl_setopt($ch, CURLOPT_URL, $parseUrl);        $html = curl_exec($ch);        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);        curl_close($ch);        echo "Response from server - " . $httpCode . ", from proxy - " . $proxy . ".\n";        //формирование ответа        if ($httpCode === 301 || $httpCode === 302) {            echo "A redirect has occurred!\n";            return false;        } elseif ($httpCode === 404) {            return false;        } else {            //проверка кодировки и последующий возврат html            return $this->checkHelper->checkEncoding($html, $domain);        }    }}