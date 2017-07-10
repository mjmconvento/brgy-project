<a class="btn btn-block btn-success" id="add_new_btn" href="/tax/{{ $constituent->id }}">
    Add New                    
</a>

<table id="dt_taxes" class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>Month</th>
            <th>Year</th>
            <th>Amount</th>
            <th>Status</th>
            <th width="100px">Edit</th>
            <th width="100px">Delete</th>
        </tr>
    </thead>
    <tbody>
        @foreach($constituent->tax as $t)
        <tr>
            <td>{{ $t->payment_month }}</td>
            <td>{{ $t->payment_year }}</td>
            <td>{{ $t->amount }}</td>
            <td>{{ $t->status }}</td>
            <td>
                <a class="btn btn-block btn-primary" href="/tax/{{ $constituent->id }}/{{ $t->id }}">
                    Edit
                </a>
            </td>
            <td>
                <form action="/tax/{{ $t->id }}" method="POST">
                    <input type="hidden" name="_method" value="DELETE">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input class="btn btn-block btn-danger" type="submit" value="DELETE">
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>