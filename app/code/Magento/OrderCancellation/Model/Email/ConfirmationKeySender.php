<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\OrderCancellation\Model\Email;

use Magento\Framework\App\Area;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Container\OrderIdentity;

/**
 * To send order return confirmation key related email
 */
class ConfirmationKeySender
{
    /**
     * Email template id
     */
    private const TEMPLATE_ID = 'sales_cancellation_confirm_guest';

    /**
     * Order view page
     */
    private const ORDER_VIEW_PATH = 'sales/guest/view';

    /**
     * ConfirmationKeySender constructor
     *
     * @param TransportBuilder $transportBuilder
     * @param Escaper $escaper
     * @param OrderIdentity $orderIdentity
     * @param UrlInterface $url
     */
    public function __construct(
        private readonly TransportBuilder $transportBuilder,
        private readonly Escaper $escaper,
        private readonly OrderIdentity $orderIdentity,
        private readonly UrlInterface $url
    ) {
    }

    /**
     * Send email to guest user with confirmation key.
     *
     * @param Order $order
     * @param string $confirmationKey
     * @return void
     * @throws LocalizedException
     */
    public function execute(
        Order $order,
        string $confirmationKey,
    ):void {
        try {
            $storeId = (int)$order->getStoreId();
            $guestOrderUrl = $this->url->getUrl(
                self::ORDER_VIEW_PATH,
                [
                    '_query' => [
                        'confirmation_key' => $confirmationKey,
                        'order_id' => $order->getIncrementId()
                    ]
                ]
            );
            $templateParams = [
                'customer_name' => $order->getCustomerName(),
                'order_id' => $order->getIncrementId(),
                'guest_order_url' => $guestOrderUrl,
                'escaper' => $this->escaper,
            ];

            $this->transportBuilder
                ->setTemplateIdentifier(self::TEMPLATE_ID)
                ->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => $storeId])
                ->setTemplateVars($templateParams)
                ->setFromByScope($this->orderIdentity->getEmailIdentity(), $storeId)
                ->addTo($order->getCustomerEmail(), $order->getCustomerName())
                ->getTransport()
                ->sendMessage();
        } catch (MailException $e) {
            throw new LocalizedException(__('Email sending failed: %1', $e->getMessage()));
        }
    }
}
