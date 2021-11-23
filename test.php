<?php

class OrderModel {
    public function create(int $orderAccountId, int $amount) {
        DB::table('orders')->insert(array(
            'account'    => $orderAccountId,
            'amount'     => $amount,
            'created_at' => Carbon::now()
        ));
    }

    public function get(int $orderAccountId, int $timestamps) {
        return DB::table('orders')
        ->where('account', $orderAccountId)
        ->where('created_at', '>=', $timestamps)
        ->count();
    }
}

class OrderProcessor {

    public function __construct(BillerInterface $biller)
    {
        $this->biller = $biller;
    }

    public function process(Order $order)
    {
        $recent = $this->getRecentOrderCount($order);

        if ($recent > 0)
        {
            throw new Exception('Duplicate order likely.');
        }

        $this->biller->bill($order->account->id, $order->amount);
        OrderModel::create($order->account->id, $order->amount);
    }

    protected function getRecentOrderCount(Order $order)
    {
        $timestamp = Carbon::now()->subMinutes(5);
        return OrderModel::get($order->account->id, $timestamps);
    }
}


class OrderProcessorTest extends TestCase {
    public function testProcess() {
        $orderProcessor = new OrderProcessor();
        $mock = \Mockery::mock(Order::class);
        $mock->shouldReceive('account')->shouldReceive('id')->andReturn(10);
        $mock->shouldReceive('amount')->andReturn(100);
        $orderProcessor->process($mock);
    }

    public function testGetRecentOrderCount() {
        $orderProcessor = new OrderProcessor();
        $mock = \Mockery::mock(Order::class);
        $mock->shouldReceive('account')->shouldReceive('id')->andReturn(10);
        $orderProcessor->getRecentOrderCount($mock);
    }
}

