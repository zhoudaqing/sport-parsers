<?php

namespace parsersPicksgrail\boards\devbmbetscom;

use parsersPicksgrail\Parser;
use parsersPicksgrail\helpers\DBHelper;

class DevBmbetsComParser extends Parser
{
    protected $newUrlOfCategory;

    function __construct($urlOfCategory, $domain, $days, $config)
    {
        parent::__construct($urlOfCategory, $domain, $days, $config);
    }

    public function getCookies()
    {
        $cleanDomain = str_replace('.', '', $this->domain);

        $path = getcwd() . "\boards\\" . $cleanDomain . "\cookies.txt";

        return $path;
    }

    public function getHeaders()
    {

        $headers = [
            //не актуально стало 21.06
            //'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            //'Accept-Language:ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            //'Connection:keep-alive',
            //'Cache-Control:max-age=0',
            //'Cookie:__cfduid=dc6a2b03ecf6fd1654d8e5dc4582544e01497892649; Language=en-US; __RequestVerificationToken=h-2Bj9CoWT0vX76BuUy1-it8M01IS5o9X6VSEoabW8ueRVa4WMEaQ1cTVY-GK6fbtaAVy1BpoBQpO3WeBHmAF1mqjT1EUK7SbSa6hRl2kko1; _hjIncludedInSample=1; gmt=0; cf_clearance=bfdebc0f61bb84437a4a9bee3dc34bc96eeec8aa-1498035783-3600; IsWelcome=1; _hjMinimizedPolls=144243; _ga=GA1.2.710389148.1497892658; _gid=GA1.2.894094897.1497892658',
            //'Host:dev.bmbets.com',
            //'Upgrade-Insecure-Requests:1',
            //'User-Agent:Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.104 Safari/537.36',

            'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            //'Accept-Encoding:gzip, deflate',
            'Accept-Language:ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            'Cache-Control:max-age=0',
            'Connection:keep-alive',
            'Cookie:__cfduid=dc6a2b03ecf6fd1654d8e5dc4582544e01497892649; Language=en-US; __RequestVerificationToken=h-2Bj9CoWT0vX76BuUy1-it8M01IS5o9X6VSEoabW8ueRVa4WMEaQ1cTVY-GK6fbtaAVy1BpoBQpO3WeBHmAF1mqjT1EUK7SbSa6hRl2kko1; _hjIncludedInSample=1; gmt=0; cf_clearance=54183896d80848d2b19e1c0d422e2e782fabf0fb1-1498140481-3600; IsWelcome=1; _hjMinimizedPolls=144243; _ga=GA1.2.710389148.1497892658; _gid=GA1.2.894094897.1497892658',
            'Host:dev.bmbets.com',
            'Upgrade-Insecure-Requests:1',
            'User-Agent:Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.109 Safari/537.36',


        ];

        return $headers;
    }

    public function getUrlsOnEvents($url, $forWhatDay)
    {
        $url = $this->createUrl($url, $forWhatDay);

        dump($url);

        $html = $this->getHtmlContentFromUrl($url);

        //получаем объект из всех актуальных событий
        $object = $this->getHtmlObject($html, 'tr[class="main-table-row  "]');

        $countEvents = count($object);

        $arrayEventsUrls = [];

        //собираем все ссылки в единый массив
        for ($i = 0; $i < $countEvents; $i++) {

            $partOfUrl = $object[$i]->children[2]->children[0]->children[0]->attr['href'];

            //формируем полную ссылку
            $link = "http://" . $this->domain . $partOfUrl;

            if (strpos($link, 'com/') !== false) {

                //преобразование html сущностей в нормальный текст
                $arrayEventsUrls[] = html_entity_decode($link);

            }

        }

        return $arrayEventsUrls;
    }

    protected function createUrl($url, $forWhatDay)
    {
        if ($forWhatDay === 1) {

            $url .= "/" . date('Ymd');

        } else {

            $url .= "/" . (date('Ymd') + ($forWhatDay - 1));

        }

        $this->newUrlOfCategory = $url;

        return $url;
    }

    protected function getTime($html)
    {
        $beginDate = $this->getBeginDate($html);

        $beginTime = $this->getBeginTime($html);

        $resultTime["date_event"] = $beginDate . " " . $beginTime;

        return $resultTime;
    }

    protected function getBeginDate($html)
    {
        $arrayWithDate = explode('/', $this->newUrlOfCategory);

        $wrongDate = $arrayWithDate[5];

        //достаем год
        $year = substr($wrongDate, 0, 4);

        //достаем месяц
        $month = substr($wrongDate, 4, 2);

        //достаем день
        $day = substr($wrongDate, 6, 4);

        //собираем все в едино
        $resultDate = $year . "-" . $month . "-" . $day;

        return $resultDate;
    }

    protected function getBeginTime($html)
    {
        $object = $this->getHtmlObject($html, '.match-info > div');

        $time = trim($object[2]->plaintext);

        $resultTime = $time . ":00";

        return $resultTime;
    }

    protected function getTypeSport($html)
    {
        $object = $this->getHtmlObject($html, 'li a span.hidden-480');

        $typeSport['type_sport'] = trim($object[0]->plaintext);

        return $typeSport;

    }

    protected function getCountry($html)
    {
        $country['name'] = $this->getCountryName($html);

        $country['css'] = $this->getCountryCss($html);

        $resultArray['country'] = $country;

        return $resultArray;
    }

    protected function getCountryCss($html)
    {
        $object = $this->getHtmlObject($html, 'ul.breadcrumb li a i.fa');

        $name = trim(str_replace('fa ', '', $object[0]->attr['class']));

        return $name;
    }

    protected function getCountryName($html)
    {
        $object = $this->getHtmlObject($html, 'li a span.hidden-480');

        $name = html_entity_decode(trim($object[1]->plaintext));

        return $name;
    }

    protected function getChampionship($html)
    {
        $result = [];

        //получение названия чемпионата
        $result['name_tournament'] = $this->getChampionshipName($html);

        $result['id_tournament'] = $this->getChampionshipId($html);

        return $result;
    }

    protected function getChampionshipName($html)
    {
        $object = $this->getHtmlObject($html, 'ul.breadcrumb');

        //подстраховка на случай, если порядок в хлебных крошках собьется
        if (count($object[0]->children) === 3) {

            $result = trim($object[0]->children[2]->plaintext);

            return $result;
        }

        return '';
    }

    protected function getChampionshipId($html)
    {
        $arrayWithId = explode('-', $this->urlOnEvent);

        $resultId = (int)array_pop($arrayWithId);

        return $resultId;
    }

    protected function getNameEvent($html)
    {
        $object = $this->getHtmlObject($html, 'title');

        $dirtyName = trim($object[0]->plaintext);

        $arrayWithName = explode(',', $dirtyName);

        $arrayWithCleanNames = explode('vs', $arrayWithName[0]);

        $arrayWithCleanNames[0] = "<span><strong>" . trim($arrayWithCleanNames[0]) . "</span></strong>";
        $arrayWithCleanNames[1] = "<span>" . trim($arrayWithCleanNames[1]) . "</span>";

        $nameEvent['name'] = $arrayWithCleanNames[0] . " - " . $arrayWithCleanNames[1];

        return $nameEvent;
    }

    public function getMarkets($html)
    {
        //костыль:( TODO сделать регулярку
        //получение json со всеми рынками с html
        $dirtyJson = $this->getDirtyJsonMarkets($html);

        $arrayWrongJson = explode(']},{', $dirtyJson);

        $count = count($arrayWrongJson);

        $resultArray = [];

        //получение рынков
        for ($i = 0; $i < $count; $i++) {

            $arraySingleMarket = [];

            $wrongJson = $arrayWrongJson[$i];

            //слегка поправим json, чтобы был рабочий
            $correctJson = $this->correctionJson($wrongJson, $count, $i);

            //переводим с json в читабельный вид
            $data = json_decode($correctJson);

            //вытягиваем имя рынка
            $arraySingleMarket['market_name'] = $data->n;

            //вытягиваем айди рынка
            $arraySingleMarket['market_id'] = $data->id;

            //вытягиваем таймауты рынка
            $arraySingleMarket['time_outs'] = $this->getTimeOuts($data);

            $resultArray[] = $arraySingleMarket;

        }

        $resultArrayMarkets['markets'] = $resultArray;

        return $resultArrayMarkets;
    }

    protected function correctionJson($wrongJson, $count, $i)
    {
        //для json, который оканчивается на пустой массив
        if ($wrongJson{$count - 1} === "[") {
            $wrongJson = $wrongJson . "]}";
        }

        //вначале добавляем скобку, кроме 1-ого элемента в массиве
        if ($i !== 0) {
            $wrongJson = "{" . $wrongJson;
        }

        //вконце добавляем скобки, крое последнего элемента в массиве
        if ($i !== $count - 1) {
            $wrongJson = $wrongJson . "]}";
        }

        return $wrongJson;
    }

    public function getBookmakers($html)
    {
        //костыль:( TODO сделать регулярку
        //получение json со всеми букмейкерами с html
        $json = $this->getJsonBookmakers($html);

        $jsonObject = json_decode($json);

        $arrayBookmakers = [];

        //собираем все конторы
        for ($i = 0; $i < 200; $i++) {

            $arraySingleBookmaker = [];

            //если есть айди
            if (!is_null($jsonObject->$i->id)) {

                $arraySingleBookmaker["id"] = $jsonObject->$i->id;

            }

            //если есть имя
            if (!is_null($jsonObject->$i->name)) {

                $arraySingleBookmaker["name"] = $jsonObject->$i->name;

                //компануем
                $arrayBookmakers[] = $arraySingleBookmaker;

            }
        }

        $resultArray["bookmakers"] = $arrayBookmakers;

        return $resultArray;
    }

    protected function getJsonBookmakers($html)
    {
        $array1 = explode('Global.Bookmakers =  ', $html);

        $array2 = explode('Global.OddFormat = 0;', $array1[1]);

        $json = str_replace(';', '', trim($array2[0]));

        return $json;
    }

    protected function getTimeOuts($data)
    {
        $resultArray = [];

        $count = count($data->t);

        for ($i = 0; $i < $count; $i++) {

            $singleTimeOut = [];

            $singleTimeOut["time_out_name"] = trim($data->t[$i]->n);

            $singleTimeOut["time_out_id"] = trim($data->t[$i]->id);

            $resultArray[] = $singleTimeOut;

        }

        return $resultArray;
    }

    protected function getDirtyJsonMarkets($html)
    {
        $array1 = explode('$("#typetabs").tabcontrol({', $html);

        $array2 = explode('});', $array1[1]);

        $dirtyJson = str_replace('data:  [', '', trim($array2[0]));

        $dirtyJson = str_replace('}]}]', '}]}', $dirtyJson);

        //>преобразование в правильный вид json: замена ковычек на двойные, и добавление их к свойствам
        $dirtyJson = str_replace('\'', '"', $dirtyJson);
        $dirtyJson = str_replace('{n:', '{"n":', $dirtyJson);
        $dirtyJson = str_replace(',k:', ',"k":', $dirtyJson);
        $dirtyJson = str_replace(',id:', ',"id":', $dirtyJson);
        $dirtyJson = str_replace(',sn:', ',"sn":', $dirtyJson);
        $dirtyJson = str_replace(',t:', ',"t":', $dirtyJson);
        //<

        return $dirtyJson;
    }

    public function putEventsInDataBase($events)
    {
        $count = count($events);

        //запись в бд по-одному событию
        for ($indexEvent = 0; $indexEvent < $count; $indexEvent++) {

            //конторы
            $this->putInBookmakers($events, $indexEvent);
            //страны
            $this->putInCountry($events, $indexEvent);
            //само событие
            $this->putInEvent($events, $indexEvent);
            //рынки
            $this->putInMarket($events, $indexEvent);
            //данные вида спорта
            $this->putInSport($events, $indexEvent);
            //
            $this->putInSportCountry($events, $indexEvent);
            //турниры
            $this->putInTournament($events, $indexEvent);

        }
    }

    protected function putInTournament($events, $indexEvent)
    {
        //достаем имя турнира
        $putArray["name"] = $events[$indexEvent]["name_tournament"];

        //получаем часть ссылки на турнир
        $arrayPartsLink = explode('/', $events[$indexEvent]["link"]);

        //собираем нужную часть ссылки
        $putArray["link"] = "/" . $arrayPartsLink[3] . "/" . $arrayPartsLink[4] . "/" . $arrayPartsLink[5] . "/";

        //проверка на дубли
        $result = $this->dbHelper->query("SELECT * FROM tournament2 WHERE link=(?s)", $putArray["link"]);

        if (!$result) {
            //записываем все в бд
            $this->dbHelper->query("INSERT INTO tournament2 (?#) VALUES (?a)", array_keys($putArray), array_values($putArray));
        }
    }

    protected function putInSportCountry($events, $indexEvent)
    {
        $arrayPartsLink = explode('/', $events[$indexEvent]["link"]);

        //собираем нужную часть ссылки
        $partLink = "/" . $arrayPartsLink[3] . "/" . $arrayPartsLink[4] . "/";

        //проверка на дубли
        $result = $this->dbHelper->query("SELECT * FROM sport_country2 WHERE link=(?s)", $partLink);

        if (!$result) {
            //записываем все в бд
            $this->dbHelper->query("INSERT INTO sport_country2 (link) VALUES (?s)", $partLink);
        }
    }

    protected function putInSport($events, $indexEvent)
    {
        //вид спорта
        $putArray["name"] = $events[$indexEvent]["type_sport"];

        //>>>получение ссылки на категорию спорта
        $arrayPartsLink = explode('/', $events[$indexEvent]["link"]);

        $count = count($arrayPartsLink);

        $newArray = [];

        for ($i = 0; $i < $count; $i++) {

            //оставляем нужные части ссылки
            if ($i < 4) {

                $newArray[] = $arrayPartsLink[$i];

            }
        }

        $putArray["link"] = implode("/", $newArray) . "/";
        //<<<

        //проверка на дубли
        $result = $this->dbHelper->query("SELECT * FROM sport2 WHERE name=(?s)", $putArray["name"]);

        if (!$result) {
            //записываем все в бд
            $this->dbHelper->query("INSERT INTO sport2 (?#) VALUES (?a)", array_keys($putArray), array_values($putArray));
        }
    }

    protected function putInMarket($events, $indexEvent)
    {
        //берем все рынки события
        $arrayMarkets = $events[$indexEvent]["markets"];

        //считаем их
        $countMarkets = count($arrayMarkets);

        //проходимся по всем рынкам
        for ($i = 0; $i < $countMarkets; $i++) {

            //считаем кол-во таймаутов
            $countPeriods = count($arrayMarkets[$i]["time_outs"]);

            //проходимся по всем таймаутам
            for ($u = 0; $u < $countPeriods; $u++) {

                //формирование массива для записи в бд
                $putArray["id"] = $arrayMarkets[$i]["time_outs"][$u]["time_out_id"];
                $putArray["name"] = $arrayMarkets[$i]["market_name"];
                $putArray["period"] = $arrayMarkets[$i]["time_outs"][$u]["time_out_name"];

                //проверка на дубли
                $result = $this->dbHelper->query("SELECT * FROM market2 WHERE id=(?s)", $putArray["id"]);

                if (!$result) {
                    //записываем все в бд
                    $this->dbHelper->query("INSERT INTO market2 (?#) VALUES (?a)", array_keys($putArray), array_values($putArray));
                }
            }
        }
    }

    protected function putInEventMarket($events, $indexEvent)
    {
    }

    protected function putInEvent($events, $indexEvent)
    {
        //объединяем все требуещиеся данные в одно место
        $putArray["id_tournament"] = $events[$indexEvent]["id_tournament"];
        $putArray["date_event"] = $events[$indexEvent]["date_event"];
        $putArray["name"] = $events[$indexEvent]["name"];
        $putArray["link"] = $events[$indexEvent]["link"];

        //проверка на дубли
        $result = $this->dbHelper->query("SELECT * FROM event2 WHERE date_event=(?s) AND link=(?s)",
            $putArray["date_event"], $putArray["link"]);

        //добавление\обновление данных
        if ($result) {
            $this->dbHelper->query("UPDATE event2 SET ?a", $putArray);
        } else {
            $this->dbHelper->query("INSERT INTO event2 (?#) VALUES (?a)", array_keys($putArray), array_values($putArray));
        }
    }

    protected function putInBookmakers($events, $indexEvent)
    {
        $count = count($events[$indexEvent]['bookmakers']);

        for ($i = 0; $i < $count; $i++) {

            $array = $events[$indexEvent]['bookmakers'][$i];

            $this->dbHelper->query("INSERT INTO bookmaker2 (?#) VALUES (?a)", array_keys($array), array_values($array));

        }
    }

    protected function putInCountry($events, $indexEvent)
    {
        $array = $events[$indexEvent]['country'];

        //проверка на дубли
        $result = $this->dbHelper->query("SELECT name FROM country2 WHERE name=(?s)", $array["name"]);

        if (!$result) {

            $this->dbHelper->query("INSERT INTO country2 (?#) VALUES (?a)", array_keys($array), array_values($array));

        }
    }

    /* получение коэффициэнтов в онлайн режиме
    protected function getJson($eventId, $idBookmaker)
    {

        $cookies = $this->getCookies();

        $headers = $this->getHeaders();

        $data = [
            "eId" => $eventId,
            "bId" => $idBookmaker,
        ];

        $ch = curl_init('http://dev.bmbets.com/oddsdata');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $result = curl_exec($ch);

        return $result;

    }
    */

    /*
    protected function getDefaultIdMarket($html)
    {

        //TODO убрать это в отдельный класс для хранения информации
        $arrayKeys = [

            3 => "Football",
            53 => "Ice Hockey",
            5 => "Tennis",
            88 => "Basketball",
            114 => "Volleyball",
            260 => "Handball",
            109 => "Baseball",
            92 => "Boxing",
            323 => "Field hockey",
            122 => "Beach Volleyball",
            129 => "Rugby Union",
            0 => "Snooker",//нет событий!! Нужно отслеживать
            233 => "Amer. Football",
            141 => "Futsal",
            144 => "Chess",
            134 => "Table Tennis",
            197 => "Aussie Rules",
            125 => "Cricket",
            153 => "Badminton",
            0 => "Floorball",//нет событий!! Нужно отслеживать
            0 => "Combat sports",//нет событий!! Нужно отслеживать
            172 => "Lacrosse",
            0 => "Bowls",//нет событий!! Нужно отслеживать
            112 => "Rugby League",
            410 => "E Sports",
            0 => "Horse Racing",//нет событий!! Нужно отслеживать
            1610 => "Softball",
            1742 => "Netball",

        ];

        $type = $this->getTypeSport($html);

        $resultKey = array_search($type['type_sport'], $arrayKeys);

        return $resultKey;

    }
    */

}