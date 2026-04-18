<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - User List</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <div class="container">
        <h2>User List</h2>

        <form method="GET" action="{{ url('/admin') }}">
    <div class="order-by">
        <label for="order_by">Order By: </label>
        <select name="order_by" id="order_by" onchange="this.form.submit()">
            <option value="username_asc" {{ request()->order_by == 'username_asc' ? 'selected' : '' }}>Username (A-Z)</option>
            <option value="username_desc" {{ request()->order_by == 'username_desc' ? 'selected' : '' }}>Username (Z-A)</option>
                <option value="email_asc" {{ request()->order_by == 'email_asc' ? 'selected' : '' }}>Email (A-Z)</option>
                <option value="email_desc" {{ request()->order_by == 'email_desc' ? 'selected' : '' }}>Email (Z-A)</option>
            </select>
        </div>
    </form>
        
        <table>
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->username }}</td>
                        <td>{{ $user->created_at }}</td>
                        <td>
                            <form action="{{ url('/admin/users/' . $user->id) }}" method="POST" onsubmit="return confirmDelete()">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="delete-btn">X</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <script>
    function confirmDelete() {
        return confirm("Are you sure you want to delete this user?");
    }
    </script>
</body>
</html>