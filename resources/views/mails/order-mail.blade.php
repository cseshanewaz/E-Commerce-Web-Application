<!DOCTYPE html>
<html lang="en">
<head>
     <meta charset="UTF-8">
     <meta http-equiv="X-UA-Compatible" content="IE=edge">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Order Confirmation</title>
</head>
<body>
     <p>Hi {{$order->first_name}} {{$order->last_name}}</p>
     <p>Your order has been successfully placed.</p>

     <table style="width: 600px; text-align:right;">
          <thead>
               <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Quantity</th>
                    <th>Price</th>
               </tr>
          </thead>
          <tbody>
               @foreach ($order->orderItems as $item)
                    <tr>
                         <td><img src="{{asset('assets/images/products')}}/{{$item->product->image}}" width="100" /></td>
                         <td>{{$item->product->name}}</td>
                         <td>{{$item->quantity}}</td>
                         <td>${{$item->price * $item->quantity}}</td>
                    </tr>
               @endforeach
               <tr>
                    <td colspan="3" style="border-too: 1px solid #ccc;"></td>
                    <td style="font-size: 15px; font-wight: bold; ">Subtotal : ${{$order->subtotal}}</td>
               </tr>
               <tr>
                    <td colspan="3"></td>
                    <td style="font-size: 15px; font-wight: bold;" >Tax : ${{$order->tax}}</td>
               </tr>
               <tr>
                    <td colspan="3"></td>
                    <td style="font-size: 15px; font-wight: bold;">Shipping : Free</td>
               </tr>
               <tr>
                    <td colspan="3"></td>
                    <td style="font-size: 22px; font-wight: bold;">Total : ${{$order->total}}</td>
               </tr>
          </tbody>
     </table>
</body>
</html>