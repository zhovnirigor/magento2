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

namespace Magento\OrderCancellationGraphQl\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\OrderCancellation\Model\Email\ConfirmationKeySender;
use Magento\OrderCancellation\Model\GetConfirmationKey;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\SalesGraphQl\Model\Formatter\Order as OrderFormatter;

class CancelOrderGuest
{
    /**
     * CancelOrderGuest Constructor
     *
     * @param OrderFormatter $orderFormatter
     * @param OrderRepositoryInterface $orderRepository
     * @param ConfirmationKeySender $confirmationKeySender
     * @param GetConfirmationKey $confirmationKey
     */
    public function __construct(
        private readonly OrderFormatter           $orderFormatter,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ConfirmationKeySender    $confirmationKeySender,
        private readonly GetConfirmationKey       $confirmationKey,
    ) {
    }

    /**
     * Generates and sends a cancellation confirmation key to the guest email
     *
     * @param Order $order
     * @param array $input
     * @return array
     */
    public function execute(Order $order, array $input): array
    {
        try {
            // send confirmation key and order id
            $this->sendConfirmationKeyEmail($order, $input['reason']);

            return [
                'order' => $this->orderFormatter->format($order)
            ];
        } catch (LocalizedException $exception) {
            return [
                'error' => __($exception->getMessage())
            ];
        }
    }

    /**
     * Sends a confirmation key and order id to the guest email which can be used to cancel the guest order
     *
     * @param Order $order
     * @param string $reason
     * @return void
     * @throws LocalizedException
     */
    private function sendConfirmationKeyEmail(Order $order, string $reason): void
    {
        $confirmationKey = $this->confirmationKey->execute($order, $reason);
        $this->confirmationKeySender->execute($order, $confirmationKey);

        // add comment in order about confirmation key send
        $order->addCommentToStatusHistory(
            'Order cancellation confirmation key was sent via email.',
            true
        );
        $this->orderRepository->save($order);
    }
}
