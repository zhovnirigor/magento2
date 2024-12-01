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

namespace Magento\OrderCancellationGraphQl\Model\Validator;

use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Ensure all conditions to cancel guest order are met
 */
class ValidateGuestRequest
{
    /**
     * Ensure the input to cancel guest order is valid
     *
     * @param mixed $input
     * @return void
     * @throws GraphQlInputException
     */
    public function validateInput(mixed $input): void
    {
        if (!is_array($input) || empty($input)) {
            throw new GraphQlInputException(
                __('GuestOrderCancelInput is missing.')
            );
        }

        if (!$input['token'] || !is_string($input['token'])) {
            throw new GraphQlInputException(
                __(
                    'Required parameter "%field" is missing or incorrect.',
                    [
                        'field' => 'token'
                    ]
                )
            );
        }

        if (!$input['reason'] || !is_string($input['reason'])) {
            throw new GraphQlInputException(
                __(
                    'Required parameter "%field" is missing or incorrect.',
                    [
                        'field' => 'reason'
                    ]
                )
            );
        }
    }

    /**
     * Ensure the order matches the provided criteria
     *
     * @param OrderInterface $order
     * @param string $postcode
     * @param string $email
     * @return void
     * @throws GraphQlAuthorizationException
     * @throws GraphQlNoSuchEntityException
     */
    public function validateOrderDetails(OrderInterface $order, string $postcode, string $email): void
    {
        $billingAddress = $order->getBillingAddress();

        if ($billingAddress->getPostcode() !== $postcode || $billingAddress->getEmail() !== $email) {
            $this->cannotLocateOrder();
        }

        if ($order->getCustomerId()) {
            throw new GraphQlAuthorizationException(__('Please login to view the order.'));
        }
    }

    /**
     * Throw exception when the order cannot be found or does not match the criteria
     *
     * @return void
     * @throws GraphQlNoSuchEntityException
     */
    public function cannotLocateOrder(): void
    {
        throw new GraphQlNoSuchEntityException(__('We couldn\'t locate an order with the information provided.'));
    }
}
