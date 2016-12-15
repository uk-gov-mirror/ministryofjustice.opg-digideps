<?php

namespace AppBundle\Service;

use AppBundle\Entity\Odr\Odr;

class OdrStatusService
{
    const STATE_NOT_STARTED = 'not-started';
    const STATE_INCOMPLETE = 'incomplete';
    const STATE_DONE = 'done';

    /** @var Odr */
    private $odr;

    public function __construct(Odr $odr)
    {
        $this->odr = $odr;
    }


    /** @return string */
    public function getBankAccountsState()
    {
        if (empty($this->odr->getBankAccounts())) {
            return self::STATE_NOT_STARTED;
        }

        return self::STATE_DONE;
    }


    /** @return string */
    public function getVisitsCareState()
    {
        if (!$this->odr->getVisitsCare()) {
            return self::STATE_NOT_STARTED;
        }
        if ($this->odr->getVisitsCare()->missingInfo()) {
            return self::STATE_INCOMPLETE;
        }
        return self::STATE_DONE;
    }


    /**
     * @return array
     */
    public function getRemainingSections()
    {
        $states = [
            'visitsCare' => $this->getVisitsCareState(),
            'assets' => $this->getAssetsState(),
            'bankAccounts' => $this->getBankAccountsState(),
            'debts' => $this->getDebtsState(),
//            'actions' => $this->getActionsState(),
        ];

        return array_filter($states, function ($e) {
            return $e != self::STATE_DONE;
        });
    }

    /** @return bool */
    public function isReadyToSubmit()
    {
        return count($this->getRemainingSections()) === 0;
    }

    /**
     * @return string $status | null
     */
    public function getStatus()
    {
        if ($this->isReadyToSubmit()) {
            return 'readyToSubmit';
        } else {
            return 'notFinished';
        }
    }


    /** @return string */
    public function getDebtsState()
    {
        $hasDebts = $this->odr->getHasDebts();

        if (empty($hasDebts)) {
            return self::STATE_NOT_STARTED;
        }

        $debtsSectionComplete = in_array($hasDebts, ['yes', 'no']);
        if ($debtsSectionComplete) {
            return self::STATE_DONE;
        }

        return self::STATE_INCOMPLETE;
    }

    /** @return string */
    public function getAssetsState()
    {
        $hasAtLeastOneAsset = count($this->odr->getAssets()) > 0;
        $noAssetsToAdd = $this->odr->getNoAssetToAdd();

        if (!$hasAtLeastOneAsset && !$noAssetsToAdd) {
            return self::STATE_NOT_STARTED;
        }

        if ($hasAtLeastOneAsset || $noAssetsToAdd) {
            return self::STATE_DONE;
        }

        return self::STATE_INCOMPLETE;
    }

    public function getActionsState()
    {
        $giftsStarted = !empty($this->odr->getActionGiveGiftsToClient());
        $propertyStarted = !empty($this->odr->getActionPropertyBuy())
            || !empty($this->odr->getActionPropertyMaintenance())
            || !empty($this->odr->getActionPropertySellingRent());
        $moreInfoStarted = !empty($this->odr->getActionMoreInfo());

        if (!$giftsStarted && !$propertyStarted && !$moreInfoStarted) {
            return self::STATE_NOT_STARTED;
        }

        if ($giftsStarted && $propertyStarted && $moreInfoStarted) {
            return self::STATE_DONE;
        }

        return self::STATE_INCOMPLETE;
    }
}
