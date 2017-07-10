<a class="btn btn-block btn-success" id="add_new_btn" href="/criminal_record/{{ $constituent->id }}">
    Add New                    
</a>
<div class="box">
    <div class="box-body no-padding">
        <table class="table table-striped">
            <tr>
                <th width="120px">Case</th>
                <th width="170px">Date</th>
                <th>Details</th>
                <th width="100px">Edit</th>
                <th width="100px">Delete</th>
            </tr>
            @foreach($constituent->criminalRecord as $r)
            <tr>
                <td>{{ $r->case_name }}</td>
                <td>{{ $r->execution_date }}</td>
                <td>{{ $r->details }}</td>
                <td>
                    <a class="btn btn-block btn-primary" href="/criminal_record/{{ $constituent->id }}/{{ $r->id }}">
                        Edit
                    </a>
                </td>
                <td>
                    <form action="/criminal_record/{{ $r->id }}" method="POST">
                        <input type="hidden" name="_method" value="DELETE">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input class="btn btn-block btn-danger" type="submit" value="DELETE">
                    </form>
                </td>
            </tr>
            @endforeach
        </table>
    </div>
</div>