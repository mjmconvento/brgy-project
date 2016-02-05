@extends('template')
@section('content')

<h1>Details</h1>
<hr/>

<h4>First Name: <a href="#" class="name">{{ $constituent->first_name }}</a></h4>
<h4>Middle Name: <a href="#" class="name">{{ $constituent->middle_name }}</a></h4>
<h4>Last Name: <a href="#" class="name">{{ $constituent->last_name }}</a></h4>
<h4>Address: <a href="#" class="name">{{ $constituent->address }}</a></h4>

<iframe width="100%" height="450" frameborder="0" style="border:0" src="https://www.google.com/maps/embed/v1/place?q={{ $constituent->address }}&key=AIzaSyDG8EMkMVj6KIRSSTiARzGwiT1aHvIWVPQ">
</iframe>


<hr/>
<hr/>
<h1>Criminal Records</h1>
<hr/>


@endsection