<?php

namespace App\Http\Livewire;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderMail;
use App\Models\Shipping;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Cart;
use Stripe;

class CheckoutComponent extends Component
{
    public $ship_to_different;
    public $first_name;
    public $last_name;
    public $email;
    public $mobile;
    public $line1;
    public $line2;
    public $city;
    public $province;
    public $country;
    public $zip_code;

    public $s_first_name;
    public $s_last_name;
    public $s_email;
    public $s_mobile;
    public $s_line1;
    public $s_line2;
    public $s_city;
    public $s_province;
    public $s_country;
    public $s_zip_code;

    public $payment_mode;
    public $thankyou;

    public $card_no;
    public $exp_month;
    public $exp_year;
    public $cvc;

    public function updated($fields)
    {
        //for billing address
        $this->validateOnly($fields, [
            'first_name'=>'required',
            'last_name'=>'required',
            'email'=>'required|email',
            'mobile'=>'required|numeric',
            'line1'=>'required',
            'city'=>'required',
            'province'=>'required',
            'country'=>'required',
            'zip_code'=>'required',
            'payment_mode' => 'required'
        ]);

        //for shipping address
        if ($this->ship_to_different) 
        {
            $this->validateOnly($fields, [
                's_first_name'=>'required',
                's_last_name'=>'required',
                's_email'=>'required|email',
                's_mobile'=>'required|numeric',
                's_line1'=>'required',
                's_city'=>'required',
                's_province'=>'required',
                's_country'=>'required',
                's_zip_code'=>'required'
            ]);
        }

        if ($this->payment_mode == 'card') 
        {
            $this->validateOnly($fields, [
                'card_no' => 'required|numeric',
                'exp_month' => 'required|numeric',
                'exp_year' => 'required|numeric',
                'cvc' => 'required|numeric'
            ]);
        }
    }

    public function placeOrder()
    {
        $this->validate([
            'first_name'=>'required',
            'last_name'=>'required',
            'email'=>'required|email',
            'mobile'=>'required|numeric',
            'line1'=>'required',
            'city'=>'required',
            'province'=>'required',
            'country'=>'required',
            'zip_code'=>'required',
            'payment_mode' => 'required'
        ]);

        if ($this->payment_mode == 'card') 
        {
            $this->validate([
                'card_no' => 'required|numeric',
                'exp_month' => 'required|numeric',
                'exp_year' => 'required|numeric',
                'cvc' => 'required|numeric'
            ]);
        }

        $order = New Order();
        $order->user_id = Auth::user()->id;
        $order->subtotal = session()->get('checkout')['subtotal'];
        $order->discount = session()->get('checkout')['discount'];
        $order->tax = session()->get('checkout')['tax'];
        $order->total = session()->get('checkout')['total'];

        $order->first_name = $this->first_name;
        $order->last_name = $this->last_name;
        $order->email = $this->email;
        $order->mobile = $this->mobile;
        $order->line1 = $this->line1;
        $order->line2 = $this->line2;
        $order->city = $this->city;
        $order->province = $this->province;
        $order->country = $this->country;
        $order->zip_code = $this->zip_code;
        $order->status = 'ordered';
        $order->is_shipping_different = $this->ship_to_different ? 1:0;
        $order->save();

        foreach (Cart::instance('cart')->content() as $item) 
        {
            $orderItem = new OrderItem();
            $orderItem->product_id = $item->id;
            $orderItem->order_id = $item->id;
            $orderItem->price = $item->price;
            $orderItem->quantity = $item->qty;
            $orderItem->save();
        }

        if ($this->ship_to_different) 
        {
            $this->validate([
                's_first_name'=>'required',
                's_last_name'=>'required',
                's_email'=>'required|email',
                's_mobile'=>'required|numeric',
                's_line1'=>'required',
                's_city'=>'required',
                's_province'=>'required',
                's_country'=>'required',
                's_zip_code'=>'required'
            ]);
            
            $shipping = new Shipping();
            $shipping->order_id = $order->id;
            $shipping->first_name = $this->s_first_name;
            $shipping->last_name = $this->s_last_name;
            $shipping->email = $this->s_email;
            $shipping->mobile = $this->s_mobile;
            $shipping->line1 = $this->s_line1;
            $shipping->line2 = $this->s_line2;
            $shipping->city = $this->s_city;
            $shipping->province = $this->s_province;
            $shipping->country = $this->s_country;
            $shipping->zip_code = $this->s_zip_code;
            $shipping->save();
        }

        if ($this->payment_mode == 'cod') 
        {
            $this->makeTransaction($order->id, 'pending');
            $this->resetCart();
        }
        elseif ($this->payment_mode == 'card') 
        {
            $stripe = Stripe::make(env('STRIPE_KEY'));

            try {
                $token = $stripe->token()->create([
                    'card'=> [
                        'number' => $this->card_no,
                        'exp_month' => $this->exp_month,
                        'exp_year' => $this->exp_year,
                        'cvc' => $this->cvc
                    ]
                ]);

                if(!isset($token['id']))
                {
                    session()->flash('stripe_error', 'the stripe token was not generated correctly!');
                    $this->thankyou = 0;
                }

                $customer = $stripe->customers()->create([
                    'name' => $this->first_name . ' ' . $this->last_name,
                    'email' => $this->email,
                    'phone' => $this->mobile,
                    'address' => [
                        'line1' =>$this->line1,
                        'postal_code' =>$this->zipcode,
                        'city' => $this->city,
                        'state' => $this->state,
                        'country' => $this->country
                    ],
                    'shipping' => [
                        'name' => $this->first_name . ' ' . $this->last_name,
                        'address' => [
                            'line1' =>$this->line1,
                            'postal_code' =>$this->zipcode,
                            'city' => $this->city,
                            'state' => $this->state,
                            'country' => $this->country
                        ],
                    ],
                    'source' => $token['id']
                ]);

                $charge = $stripe->charges()->create([
                    'customer' => $customer['id'],
                    'currency' => 'USD',
                    'amount' => session()->get('checkout')['total'],
                    'description' => 'Payment for order no.' . $order->id
                ]);

                if ($charge['status'] == 'succeeded') 
                {
                    $this->makeTransaction($order->id, 'approved');
                    $this->resetCart();
                }
                else
                {
                    session()->flash('stripe_error', 'Error in Transaction and please try again.');
                    $this->thankyou = 0;
                }
            } catch(Exception $e){
                session()->flash('stripe_error', $e->getMessage());
                $this->thankyou = 0;
            }
        }
        $this->sendOrderConfirmationMail($order);       
    }
    

    public function resetCart()
    {
        $this->thankyou = 1;
        Cart::instance('cart')->destroy();
        session()->forget('checkout');
    }

    public function makeTransaction($order_id, $status)
    {
        $transaction = new Transaction();
        $transaction->user_id = Auth::user()->id;
        $transaction->order_id = $order_id;
        $transaction->mode = $this->payment_mode;
        $transaction->status = $status;
        $transaction->save();
    }

    public function sendOrderConfirmationMail($order)
    {
        Mail::to($order->email)->send(new OrderMail($order));
    }

    public function verifyForCheckout()
    {
        if(!Auth::check())
        {
            return redirect()->route('login');
        }
        else if($this->thankyou)
        {
            return redirect()->route('thankyou');
        }
        else if(!session()->get('checkout'))
        {
            return redirect()->route('product.cart');
        }
    }

    public function render()
    {
        $this->verifyForCheckout();
        return view('livewire.checkout-component')->layout('layouts.base');
    }
}
