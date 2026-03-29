@component('mail::message')
# Don hang moi #{{ $order->id }}

**Khach hang:** {{ $order->customerName }}

**So dien thoai:** {{ $order->customerPhone }}

**Dia chi giao hang:** {{ $order->deliveryAddress }}

**Phuong thuc thanh toan:** {{ $order->paymentMethod->value }}

---

## Chi tiet san pham

@component('mail::table')
| STT | Ten san pham | So luong | Don gia (VND) | Thanh tien (VND) |
|:---:|:------------|:--------:|:-------------:|:----------------:|
@foreach($order->items as $index => $item)
| {{ $index + 1 }} | {{ $item->productName }} | {{ $item->quantity }} | {{ number_format($item->priceVnd) }} | {{ number_format($item->subtotalVnd) }} |
@endforeach
@endcomponent

---

**Tong cong: {{ number_format((float) $order->totalAmount) }} VND**

Vui long kiem tra va xac nhan don hang.

@component('mail::button', ['url' => config('app.url')])
Xem don hang
@endcomponent

@endcomponent
