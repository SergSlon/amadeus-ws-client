<?php
/**
 * amadeus-ws-client
 *
 * Copyright 2015 Amadeus Benelux NV
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package Amadeus
 * @license https://opensource.org/licenses/Apache-2.0 Apache 2.0
 */

namespace Amadeus\Client\RequestCreator;

use Amadeus\Client\InvalidMessageException;
use Amadeus\Client\Params\RequestCreatorParams;
use Amadeus\Client\RequestOptions\AirFlightInfoOptions;
use Amadeus\Client\RequestOptions\AirMultiAvailabilityOptions;
use Amadeus\Client\RequestOptions\AirRetrieveSeatMapOptions;
use Amadeus\Client\RequestOptions\AirSellFromRecommendationOptions;
use Amadeus\Client\RequestOptions\CommandCrypticOptions;
use Amadeus\Client\RequestOptions\DocIssuanceIssueTicketOptions;
use Amadeus\Client\RequestOptions\FareCheckRulesOptions;
use Amadeus\Client\RequestOptions\FareConvertCurrencyOptions;
use Amadeus\Client\RequestOptions\FareInformativePricingWithoutPnrOptions;
use Amadeus\Client\RequestOptions\FareMasterPricerTbSearch;
use Amadeus\Client\RequestOptions\FarePricePnrWithBookingClassOptions;
use Amadeus\Client\RequestOptions\InfoEncodeDecodeCityOptions;
use Amadeus\Client\RequestOptions\MiniRuleGetFromPricingRecOptions;
use Amadeus\Client\RequestOptions\OfferConfirmAirOptions;
use Amadeus\Client\RequestOptions\OfferConfirmCarOptions;
use Amadeus\Client\RequestOptions\OfferConfirmHotelOptions;
use Amadeus\Client\RequestOptions\OfferVerifyOptions;
use Amadeus\Client\RequestOptions\PnrAddMultiElementsBase;
use Amadeus\Client\RequestOptions\PnrCancelOptions;
use Amadeus\Client\RequestOptions\PnrCreatePnrOptions;
use Amadeus\Client\RequestOptions\PnrDisplayHistoryOptions;
use Amadeus\Client\RequestOptions\PnrRetrieveAndDisplayOptions;
use Amadeus\Client\RequestOptions\PnrRetrieveOptions;
use Amadeus\Client\RequestOptions\PriceXplorerExtremeSearchOptions;
use Amadeus\Client\RequestOptions\QueueListOptions;
use Amadeus\Client\RequestOptions\QueueMoveItemOptions;
use Amadeus\Client\RequestOptions\QueuePlacePnrOptions;
use Amadeus\Client\RequestOptions\QueueRemoveItemOptions;
use Amadeus\Client\RequestOptions\RequestOptionsInterface;
use Amadeus\Client\RequestOptions\SalesReportsDisplayQueryReportOptions;
use Amadeus\Client\RequestOptions\SecurityAuthenticateOptions;
use Amadeus\Client\RequestOptions\TicketCreateTstFromPricingOptions;
use Amadeus\Client\RequestOptions\TicketDeleteTstOptions;
use Amadeus\Client\RequestOptions\TicketDisplayTstOptions;
use Amadeus\Client\Struct;

/**
 * Base request creator - the default request creator.
 *
 * @package Amadeus\Client\RequestCreator
 * @author Dieter Devlieghere <dieter.devlieghere@benelux.amadeus.com>
 */
class Base implements RequestCreatorInterface
{
    /**
     * @var RequestCreatorParams
     */
    protected $params;

    /**
     * Associative array of messages (as keys) and versions (as values) that are present in the WSDL.
     *
     * @var array
     */
    protected $messagesAndVersions = [];

    /**
     * @param $params
     */
    public function __construct(RequestCreatorParams $params)
    {
        $this->params = $params;
        $this->messagesAndVersions = $params->messagesAndVersions;
    }

    /**
     * @param string $messageName the message name as named in the WSDL
     * @param RequestOptionsInterface $params
     * @throws Struct\InvalidArgumentException When invalid input is detected during message creation.
     * @throws InvalidMessageException when trying to create a request for a message that is not in your WSDL.
     * @return mixed the created request
     */
    public function createRequest($messageName, RequestOptionsInterface $params)
    {
        $this->checkMessageIsInWsdl($messageName);

        $methodName = 'create' . str_replace("_", "", $messageName);

        if (method_exists($this, $methodName)) {
            return $this->$methodName($params);
        } else {
            throw new \RuntimeException('Message ' . $methodName . ' is not implemented in ' . __CLASS__);
        }
    }

    /**
     * @return Struct\Security\SignOut
     */
    protected function createSecuritySignOut()
    {
        return new Struct\Security\SignOut();
    }

    /**
     * Create request object for Security_Authenticate message
     *
     * @param SecurityAuthenticateOptions $params
     * @return Struct\Security\Authenticate
     */
    protected function createSecurityAuthenticate(SecurityAuthenticateOptions $params)
    {
        return new Struct\Security\Authenticate($params);
    }

    /**
     * Create request object for PNR_Retrieve message
     *
     * @param PnrRetrieveOptions $params
     * @return Struct\Pnr\Retrieve
     */
    protected function createPNRRetrieve(PnrRetrieveOptions $params)
    {
        $retrieveRequest = new Struct\Pnr\Retrieve(
            Struct\Pnr\Retrieve::RETR_TYPE_BY_RECLOC,
            $params->recordLocator
        );

        return $retrieveRequest;
    }

    /**
     * @param PnrRetrieveAndDisplayOptions $params
     * @return Struct\Pnr\RetrieveAndDisplay
     */
    protected function createPNRRetrieveAndDisplay(PnrRetrieveAndDisplayOptions $params)
    {
        $req = new Struct\Pnr\RetrieveAndDisplay(
            $params->recordLocator,
            $params->retrieveOption
        );

        return $req;
    }

    /**
     * @param PnrAddMultiElementsBase $params
     * @return Struct\Pnr\AddMultiElements
     */
    protected function createPNRAddMultiElements(PnrAddMultiElementsBase $params)
    {
        if ($params instanceof PnrCreatePnrOptions && empty($params->receivedFrom)) {
            //Automagically add RF if not present:
            $params->receivedFrom = $this->params->receivedFrom;
        }

        $req = new Struct\Pnr\AddMultiElements($params);

        return $req;
    }

    /**
     * @param PnrCancelOptions $params
     * @return Struct\Pnr\Cancel
     */
    protected function createPNRCancel(PnrCancelOptions $params)
    {
        return new Struct\Pnr\Cancel($params);
    }

    /**
     * @param PnrDisplayHistoryOptions $params
     * @return Struct\Pnr\DisplayHistory
     */
    protected function createPNRDisplayHistory(PnrDisplayHistoryOptions $params)
    {
        return new Struct\Pnr\DisplayHistory($params);
    }

    /**
     * @param QueueListOptions $params
     * @return Struct\Queue\QueueList
     */
    protected function createQueueList(QueueListOptions $params)
    {
        $queueListRequest = new Struct\Queue\QueueList(
            $params->queue->queue,
            $params->queue->category
        );

        return $queueListRequest;
    }

    /**
     * @param QueuePlacePnrOptions $params
     * @return Struct\Queue\PlacePnr
     */
    protected function createQueuePlacePnr(QueuePlacePnrOptions $params)
    {
        $req = new Struct\Queue\PlacePnr(
            $params->recordLocator,
            $params->sourceOfficeId,
            $params->targetQueue
        );

        return $req;
    }

    /**
     * @param QueueRemoveItemOptions $params
     * @return Struct\Queue\RemoveItem
     */
    protected function createQueueRemoveItem(QueueRemoveItemOptions $params)
    {
        $req = new Struct\Queue\RemoveItem(
            $params->queue,
            $params->recordLocator,
            $params->originatorOfficeId
        );

        return $req;
    }

    /**
     * @param QueueMoveItemOptions $params
     * @return Struct\Queue\MoveItem
     */
    protected function createQueueMoveItem(QueueMoveItemOptions $params)
    {
        $req = new Struct\Queue\MoveItem(
            $params->recordLocator,
            $params->officeId,
            $params->sourceQueue,
            $params->destinationQueue
        );

        return $req;
    }

    /**
     * @param OfferVerifyOptions $params
     * @return Struct\Offer\Verify
     */
    protected function createOfferVerifyOffer(OfferVerifyOptions $params)
    {
        $req = new Struct\Offer\Verify(
            $params->offerReference,
            $params->segmentName
        );

        return $req;
    }

    /**
     * @param OfferConfirmAirOptions $params
     * @return Struct\Offer\ConfirmAir
     */
    protected function createOfferConfirmAirOffer(OfferConfirmAirOptions $params)
    {
        return new Struct\Offer\ConfirmAir($params);
    }


    /**
     * @param OfferConfirmHotelOptions $params
     * @return Struct\Offer\ConfirmHotel
     */
    protected function createOfferConfirmHotelOffer(OfferConfirmHotelOptions $params)
    {
        return new Struct\Offer\ConfirmHotel($params);
    }

    /**
     * @param OfferConfirmCarOptions $params
     * @return Struct\Offer\ConfirmCar
     */
    protected function createOfferConfirmCarOffer(OfferConfirmCarOptions $params)
    {
        return new Struct\Offer\ConfirmCar($params);
    }

    /**
     * createFareMasterPricerTravelBoardSearch
     *
     * @param FareMasterPricerTbSearch $params
     * @return Struct\Fare\MasterPricerTravelBoardSearch
     */
    protected function createFareMasterPricerTravelBoardSearch(FareMasterPricerTbSearch $params)
    {
        return new Struct\Fare\MasterPricerTravelBoardSearch($params);
    }


    /**
     * createFareCheckRules
     *
     * @param FareCheckRulesOptions $params
     * @return Struct\Fare\CheckRules
     */
    protected function createFareCheckRules(FareCheckRulesOptions $params)
    {
        return new Struct\Fare\CheckRules($params);
    }

    /**
     * createFareConvertCurrency
     *
     * @param FareConvertCurrencyOptions $params
     * @return Struct\Fare\ConvertCurrency
     */
    protected function createFareConvertCurrency(FareConvertCurrencyOptions $params)
    {
        return new Struct\Fare\ConvertCurrency($params);
    }

    /**
     * makeFarePricePnrWithBookingClass
     *
     * @param FarePricePnrWithBookingClassOptions $params
     * @return Struct\Fare\PricePNRWithBookingClass12|Struct\Fare\PricePNRWithBookingClass13
     */
    protected function createFarePricePnrWithBookingClass(FarePricePnrWithBookingClassOptions $params)
    {
        $version = $this->getActiveVersionFor('Fare_PricePNRWithBookingClass');
        if ($version < 13) {
            return new Struct\Fare\PricePNRWithBookingClass12($params);
        } else {
            return new Struct\Fare\PricePNRWithBookingClass13($params);
        }
    }

    /**
     * createFareInformativePricingWithoutPNR
     *
     * @param FareInformativePricingWithoutPnrOptions $params
     * @return Struct\Fare\InformativePricingWithoutPNR12|Struct\Fare\InformativePricingWithoutPNR13
     */
    protected function createFareInformativePricingWithoutPNR(FareInformativePricingWithoutPnrOptions $params)
    {
        $version = $this->getActiveVersionFor('Fare_InformativePricingWithoutPNR');
        if ($version < 13) {
            return new Struct\Fare\InformativePricingWithoutPNR12($params);
        } else {
            return new Struct\Fare\InformativePricingWithoutPNR13($params);
        }
    }

    /**
     * Air_MultiAvailability
     *
     * @param AirMultiAvailabilityOptions $params
     * @return Struct\Air\MultiAvailability
     */
    protected function createAirMultiAvailability(AirMultiAvailabilityOptions $params)
    {
        return new Struct\Air\MultiAvailability($params);
    }

    /**
     * Air_SellFromRecommendation
     *
     * @param AirSellFromRecommendationOptions $params
     * @return Struct\Air\SellFromRecommendation
     */
    protected function createAirSellFromRecommendation(AirSellFromRecommendationOptions $params)
    {
        return new Struct\Air\SellFromRecommendation($params);
    }

    /**
     * Air_FlightInfo
     *
     * @param AirFlightInfoOptions $params
     * @return Struct\Air\FlightInfo
     */
    protected function createAirFlightInfo(AirFlightInfoOptions $params)
    {
        return new Struct\Air\FlightInfo($params);
    }

    /**
     * @param AirRetrieveSeatMapOptions $params
     * @return Struct\Air\RetrieveSeatMap
     */
    protected function createAirRetrieveSeatMap(AirRetrieveSeatMapOptions $params)
    {
        return new Struct\Air\RetrieveSeatMap($params);
    }

    /**
     * Command_Cryptic
     *
     * @param CommandCrypticOptions $params
     * @return Struct\Command\Cryptic
     */
    protected function createCommandCryptic(CommandCrypticOptions $params)
    {
        return new Struct\Command\Cryptic($params->entry);
    }

    /**
     * Info_EncodeDecodeCity
     *
     * @param InfoEncodeDecodeCityOptions $params
     * @return Struct\Info\EncodeDecodeCity
     */
    protected function createInfoEncodeDecodeCity(InfoEncodeDecodeCityOptions $params)
    {
        return new Struct\Info\EncodeDecodeCity($params);
    }

    /**
     * makeMiniRuleGetFromPricingRec
     *
     * @param MiniRuleGetFromPricingRecOptions $params
     * @return Struct\MiniRule\GetFromPricingRec
     */
    protected function createMiniRuleGetFromPricingRec(MiniRuleGetFromPricingRecOptions $params)
    {
        return new Struct\MiniRule\GetFromPricingRec($params);
    }

    /**
     * Ticket_CreateTstFromPricing
     *
     * @param TicketCreateTstFromPricingOptions $params
     * @return Struct\Ticket\CreateTSTFromPricing
     */
    protected function createTicketCreateTSTFromPricing(TicketCreateTstFromPricingOptions $params)
    {
        return new Struct\Ticket\CreateTSTFromPricing($params);
    }

    /**
     * Ticket_DeleteTST
     *
     * @param TicketDeleteTstOptions $params
     * @return Struct\Ticket\DeleteTST
     */
    protected function createTicketDeleteTST(TicketDeleteTstOptions $params)
    {
        return new Struct\Ticket\DeleteTST($params);
    }

    /**
     * Ticket_DisplayTST
     *
     * @param TicketDisplayTstOptions $params
     * @return Struct\Ticket\DisplayTST
     */
    protected function createTicketDisplayTST(TicketDisplayTstOptions $params)
    {
        return new Struct\Ticket\DisplayTST($params);
    }

    /**
     * DocIssuance_IssueTicket
     *
     * @param DocIssuanceIssueTicketOptions $params
     * @return Struct\DocIssuance\IssueTicket
     */
    protected function createDocIssuanceIssueTicket(DocIssuanceIssueTicketOptions $params)
    {
        return new Struct\DocIssuance\IssueTicket($params);
    }

    /**
     * PriceXplorer_ExtremeSearch
     *
     * @param PriceXplorerExtremeSearchOptions $params
     * @return Struct\PriceXplorer\ExtremeSearch
     */
    protected function createPriceXplorerExtremeSearch(PriceXplorerExtremeSearchOptions $params)
    {
        return new Struct\PriceXplorer\ExtremeSearch($params);
    }

    /**
     * SalesReports_DisplayQueryReport
     *
     * @param SalesReportsDisplayQueryReportOptions $params
     * @return Struct\SalesReports\DisplayQueryReport
     */
    protected function createSalesReportsDisplayQueryReport(SalesReportsDisplayQueryReportOptions $params)
    {
        return new Struct\SalesReports\DisplayQueryReport($params);
    }

    /**
     * Check if a given message is in the active WSDL. Throws exception if it isn't.
     *
     * @throws InvalidMessageException if message is not in WSDL.
     * @param string $messageName
     */
    protected function checkMessageIsInWsdl($messageName)
    {
        if (!array_key_exists($messageName, $this->messagesAndVersions)) {
            throw new InvalidMessageException('Message "' . $messageName . '" is not in WDSL');
        }
    }

    /**
     * Get the version number active in the WSDL for the given message
     *
     * @param string $messageName
     * @return float|string
     */
    protected function getActiveVersionFor($messageName)
    {
        return $this->messagesAndVersions[$messageName];
    }
}
