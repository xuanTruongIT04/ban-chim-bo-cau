<?php

declare(strict_types=1);

use App\Domain\Order\Enums\OrderStatus;

describe('OrderStatus', function (): void {

    describe('allowedNextStates', function (): void {

        it('ChoXacNhan allows XacNhan and Huy', function (): void {
            $next = OrderStatus::ChoXacNhan->allowedNextStates();
            expect($next)->toContain(OrderStatus::XacNhan);
            expect($next)->toContain(OrderStatus::Huy);
            expect($next)->not->toContain(OrderStatus::DangGiao);
            expect($next)->not->toContain(OrderStatus::HoanThanh);
        });

        it('XacNhan allows ChoXacNhan, DangGiao, and Huy (back-step 1)', function (): void {
            $next = OrderStatus::XacNhan->allowedNextStates();
            expect($next)->toContain(OrderStatus::ChoXacNhan);
            expect($next)->toContain(OrderStatus::DangGiao);
            expect($next)->toContain(OrderStatus::Huy);
        });

        it('DangGiao allows XacNhan, HoanThanh, and Huy (back-step 1)', function (): void {
            $next = OrderStatus::DangGiao->allowedNextStates();
            expect($next)->toContain(OrderStatus::XacNhan);
            expect($next)->toContain(OrderStatus::HoanThanh);
            expect($next)->toContain(OrderStatus::Huy);
        });

        it('HoanThanh has no allowed next states (terminal)', function (): void {
            expect(OrderStatus::HoanThanh->allowedNextStates())->toBeEmpty();
        });

        it('Huy has no allowed next states (terminal)', function (): void {
            expect(OrderStatus::Huy->allowedNextStates())->toBeEmpty();
        });

    });

    describe('canTransitionTo', function (): void {

        it('allows valid forward transition', function (): void {
            expect(OrderStatus::ChoXacNhan->canTransitionTo(OrderStatus::XacNhan))->toBeTrue();
            expect(OrderStatus::XacNhan->canTransitionTo(OrderStatus::DangGiao))->toBeTrue();
            expect(OrderStatus::DangGiao->canTransitionTo(OrderStatus::HoanThanh))->toBeTrue();
        });

        it('allows back-step of 1', function (): void {
            expect(OrderStatus::XacNhan->canTransitionTo(OrderStatus::ChoXacNhan))->toBeTrue();
            expect(OrderStatus::DangGiao->canTransitionTo(OrderStatus::XacNhan))->toBeTrue();
        });

        it('allows cancellation from non-terminal states', function (): void {
            expect(OrderStatus::ChoXacNhan->canTransitionTo(OrderStatus::Huy))->toBeTrue();
            expect(OrderStatus::XacNhan->canTransitionTo(OrderStatus::Huy))->toBeTrue();
            expect(OrderStatus::DangGiao->canTransitionTo(OrderStatus::Huy))->toBeTrue();
        });

        it('rejects skip (ChoXacNhan to HoanThanh not allowed)', function (): void {
            expect(OrderStatus::ChoXacNhan->canTransitionTo(OrderStatus::HoanThanh))->toBeFalse();
        });

        it('rejects transitions from terminal states', function (): void {
            expect(OrderStatus::HoanThanh->canTransitionTo(OrderStatus::Huy))->toBeFalse();
            expect(OrderStatus::Huy->canTransitionTo(OrderStatus::ChoXacNhan))->toBeFalse();
        });

    });

    describe('isCancellable', function (): void {

        it('HoanThanh is not cancellable', function (): void {
            expect(OrderStatus::HoanThanh->isCancellable())->toBeFalse();
        });

        it('all other states are cancellable', function (): void {
            expect(OrderStatus::ChoXacNhan->isCancellable())->toBeTrue();
            expect(OrderStatus::XacNhan->isCancellable())->toBeTrue();
            expect(OrderStatus::DangGiao->isCancellable())->toBeTrue();
            expect(OrderStatus::Huy->isCancellable())->toBeTrue();
        });

    });

    describe('label', function (): void {

        it('returns non-empty string for all cases', function (): void {
            foreach (OrderStatus::cases() as $status) {
                expect($status->label())->toBeString()->not->toBeEmpty();
            }
        });

        it('returns correct Vietnamese labels', function (): void {
            expect(OrderStatus::ChoXacNhan->label())->toBe('Chờ xác nhận');
            expect(OrderStatus::XacNhan->label())->toBe('Xác nhận');
            expect(OrderStatus::DangGiao->label())->toBe('Đang giao');
            expect(OrderStatus::HoanThanh->label())->toBe('Hoàn thành');
            expect(OrderStatus::Huy->label())->toBe('Hủy');
        });

    });

});
