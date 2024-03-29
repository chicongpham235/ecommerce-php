<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
// use Symfony\Component\HttpFoundation\Session\Session;
class CheckOut extends Component
{

    public $message;
    public $fullname;

    public $email;
    public $phone;
    public $address;
    public $zipcode;
    public function placeOder1(Request $request)
    {
            $order = new Order();
            $order->user_id = Auth::user()->id;
            $order->subtotal = Cart::subtotal(0);
            $order->tax = Cart::tax();
            $order->total = Cart::total();
            $order->firstname= $this->fullname;
            $order->lastname= $this->fullname;
            $order->email= $this->email;
            $order->phone= $this->phone;
            $order->address= $this->address;
            $order->zipcode= $this->zipcode;
             $order->save();

             $request->session()->put('id',$order->id);
             $items =Cart::content();
             foreach ($items as $i) {
                $order_item = new OrderItem();
                $order_item->product_id= $i->p_id;
                $order_item->size = $i->size;
                $order_item->color = $i->color;
                $order_item->order_id= $order->id;
                $order_item->price= $i->price;
                $order_item->quantity= $i->qty;
                $order_item->save();

            }
            $vnp_TxnRef = date("YmdHis");//Mã đơn hàng. Trong thực tế Merchant cần insert đơn hàng vào DB và gửi mã này sang VNPAY
            $vnp_OrderInfo = $this->message;
            $vnp_OrderType = 'billpayment';
            $vnp_Amount =  Cart::total()*100;
            $vnp_Locale =   'vn';
            $vnp_BankCode = 'NCB';
            $vnp_IpAddr =$_SERVER['REMOTE_ADDR'];
            //Add Params of 2.0.1 Version
            $startTime = date("YmdHis");
            $expire =   date('YmdHis',strtotime('+10 minutes',strtotime($startTime)));
            $vnp_ExpireDate =  $expire;
            //Bill
            $vnp_Bill_Mobile =  $this->phone;
            $vnp_Bill_Email =   $this->email;
            $fullName = trim($this->fullname);

            if (isset($fullName) && trim($fullName) != '') {
                $name = explode(' ', $fullName);
                $vnp_Bill_FirstName = array_shift($name);
                $vnp_Bill_LastName = array_pop($name);
            }
            $vnp_Bill_Address=  $this->address;
            $temp = explode(", ", $this->address);
            $vnp_Bill_City = $temp[(count($temp)-1)];
            // $vnp_Bill_City= 'Thanh hóa';
            $vnp_Bill_Country=  'VN';
            $vnp_Bill_State=   null;
            // Invoice
            $vnp_Inv_Phone= $this->phone;
            $vnp_Inv_Email= $this->email;
            $vnp_Inv_Customer=  'txt_inv_customer';
            $vnp_Inv_Address=   '22 Láng Hạ, Phường Láng Hạ, Quận Đống Đa, TP Hà Nội';
            $vnp_Inv_Company=   'Công ty Cổ phần giải pháp Thanh toán Việt Nam';
            $vnp_Inv_Taxcode=    '0102182292';
            $vnp_Inv_Type= 'I';
            $inputData = array(
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => env('VNP_TMN_CODE'),
                "vnp_Amount" => $vnp_Amount,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $vnp_IpAddr,
                "vnp_Locale" => $vnp_Locale,
                "vnp_OrderInfo" => $vnp_OrderInfo,
                "vnp_OrderType" => $vnp_OrderType,
                "vnp_ReturnUrl" => env('VNP_RETURNURL'),
                "vnp_TxnRef" => $vnp_TxnRef,
                "vnp_ExpireDate"=>$vnp_ExpireDate,
                "vnp_Bill_Mobile"=>$vnp_Bill_Mobile,
                "vnp_Bill_Email"=>$vnp_Bill_Email,
                "vnp_Bill_FirstName"=>$vnp_Bill_FirstName,
                "vnp_Bill_LastName"=>$vnp_Bill_LastName,
                "vnp_Bill_Address"=>$vnp_Bill_Address,
                "vnp_Bill_City"=>$vnp_Bill_City,
                "vnp_Bill_Country"=>$vnp_Bill_Country,
                "vnp_Inv_Phone"=>$vnp_Inv_Phone,
                "vnp_Inv_Email"=>$vnp_Inv_Email,
                "vnp_Inv_Customer"=>$vnp_Inv_Customer,
                "vnp_Inv_Address"=>$vnp_Inv_Address,
                "vnp_Inv_Company"=>$vnp_Inv_Company,
                "vnp_Inv_Taxcode"=>$vnp_Inv_Taxcode,
                "vnp_Inv_Type"=>$vnp_Inv_Type
            );

            if ((null !== $vnp_BankCode) && $vnp_BankCode != "") {
                $inputData['vnp_BankCode'] = $vnp_BankCode;
            }
            if ((null !== $vnp_Bill_State) && $vnp_Bill_State != "") {
                $inputData['vnp_Bill_State'] = $vnp_Bill_State;
            }
            // dd($inputData);
            ksort($inputData);
            $query = "";
            $i = 0;
            $hashdata = "";
            foreach ($inputData as $key => $value) {
                if ($i == 1) {
                    $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
                } else {
                    $hashdata .= urlencode($key) . "=" . urlencode($value);
                    $i = 1;
                }
                $query .= urlencode($key) . "=" . urlencode($value) . '&';
            }
            $vnp_Url = env('VNP_URL') . "?" . $query;
            if ((null !== env('VNP_HASH_SECRET'))) {
                $vnpSecureHash =   hash_hmac('sha512', $hashdata, env('VNP_HASH_SECRET'));//
                $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
            }
            Cart::destroy();
            redirect($vnp_Url);
    }
    public function render()
    {
        $items=Cart::content();

        return view('livewire.check-out',compact('items'))->layout('layouts.base');

    }
}
