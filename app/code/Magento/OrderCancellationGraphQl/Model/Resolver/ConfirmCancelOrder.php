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

namespace Magento\OrderCancellationGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\OrderCancellationGraphQl\Model\ConfirmCancelOrder as ConfirmCancelOrderGuest;
use Magento\OrderCancellationGraphQl\Model\Validator\ValidateOrder;
use Magento\OrderCancellationGraphQl\Model\Validator\ValidateConfirmRequest;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Cancels a guest order on confirmation
 */
class ConfirmCancelOrder implements ResolverInterface
{
    /**
     * ConfirmCancelOrder Constructor
     *
     * @param ValidateConfirmRequest $validateRequest
     * @param OrderRepositoryInterface $orderRepository
     * @param ConfirmCancelOrderGuest $confirmCancelOrder
     * @param ValidateOrder $validateOrder
     */
    public function __construct(
        private readonly ValidateConfirmRequest   $validateRequest,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ConfirmCancelOrderGuest  $confirmCancelOrder,
        private readonly ValidateOrder            $validateOrder
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        $this->validateRequest->execute($args['input'] ?? []);

        try {
            $order = $this->orderRepository->get($args['input']['order_id']);

            if (!$order->getCustomerIsGuest()) {
                return [
                    'error' => __('Current user is not authorized to cancel this order')
                ];
            }

            $errors = $this->validateOrder->execute($order);
            if (!empty($errors)) {
                return $errors;
            }

            return $this->confirmCancelOrder->execute($order, $args['input']);
        } catch (LocalizedException $e) {
            return [
                'error' => __($e->getMessage())
            ];
        }
    }
}
