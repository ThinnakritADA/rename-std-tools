@extends('template.master')

@push('css')
    <style>
        .is-highlight {
            background-color: #1f2330;
            color: #d9e0e8;
            /*font-size: .75em;*/
            /*position: relative;*/
        }
    </style>
@endpush

@section('content')
<section class="hero is-primary is-medium">
    <div class="hero-body">
        <div class="container">
            <form action="{{ route('rename') }}" method="post" id="renameForm">
                @csrf
                <div class="field has-addons has-addons-fullwidth">
                    <div class="control">
                        <input class="input" type="text" name="path" placeholder="Path to controller" required>
                    </div>
                    <button class="button is-danger">
                        Process
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
<section class="hero is-highlight is-medium is-hidden" id="resultChangeList">
    <div class="hero-body pt-6">
        <div class="container">
            <div class="columns is-centered">
                <div class="column is-6" id="odvChangeListTable1">
                </div>
                <div class="column is-6" id="odvChangeListTable2">
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('js')
    <script>
        async function FSxRENChangeListTable(actionUrl, path) {
            const tableController = document.querySelector('#odvChangeListTable1');
            const tableModel = document.querySelector('#odvChangeListTable2');
            tableController.innerHTML = '';
            tableModel.innerHTML = '';
            const response = await fetch(actionUrl,{
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    path
                })
            });
            const data = await response.json();
            if(data.error) {
                console.error(data);
                alert('Something went wrong ! \nReason: ' + data.error);
                return;
            }
            if(data.success) {
                document.querySelector('#resultChangeList').classList.remove('is-hidden');
                tableController.innerHTML = '<h1 class="title has-text-white">Controller</h1>';
                tableController.innerHTML += '<table class="table is-bordered is-dark is-fullwidth">\n' +
                    '                        <thead>\n' +
                    '                        <tr>\n' +
                    '                            <th>Old Name</th>\n' +
                    '                            <th>New Name</th>\n' +
                    '                        </tr>\n' +
                    '                        </thead>\n' +
                    '                        <tbody>\n' +
                    '                        </tbody>\n' +
                    '                    </table>';
                data.success.controller.forEach((item) => {
                    tableController.querySelector('tbody').innerHTML += '<tr>\n' +
                        '                            <td>' + item.originalName + '</td>\n' +
                        '                            <td>' + item.newName + '</td>\n' +
                        '                        </tr>';
                });

                tableModel.innerHTML = '<h1 class="title has-text-white">Model</h1>';
                tableModel.innerHTML += '<table class="table is-bordered is-dark is-fullwidth">\n' +
                    '                        <thead>\n' +
                    '                        <tr>\n' +
                    '                            <th>Old Name</th>\n' +
                    '                            <th>New Name</th>\n' +
                    '                        </tr>\n' +
                    '                        </thead>\n' +
                    '                        <tbody>\n' +
                    '                        </tbody>\n' +
                    '                    </table>';
                data.success.model.forEach((item) => {
                    tableModel.querySelector('tbody').innerHTML += '<tr>\n' +
                        '                            <td>' + item.originalName + '</td>\n' +
                        '                            <td>' + item.newName + '</td>\n' +
                        '                        </tr>';
                });
            }
        }

        document.querySelector('#renameForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            this.querySelector('button').classList.add('is-loading');
            const path = document.querySelector('input[name="path"]').value;
            const actionUrl = e.target.action;
            await FSxRENChangeListTable(actionUrl, path);
            this.querySelector('button').classList.remove('is-loading');
        });
    </script>
@endpush

