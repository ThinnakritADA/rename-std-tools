@extends('template.master')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/nyancat.css') }}"/>
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
            <h1 class="title">
                กรุณากรอก Path ที่ต้องการเปลี่ยนชื่อ
            </h1>
            <h4 class="subtitle">
                ตัวอย่างเช่น <code>C:/xampp/htdocs/AdaStoreBack</code>
            </h4>
            <form action="{{ route('rename') }}" method="post" id="ofmRenameForm">
                @csrf
                <div class="field has-addons has-addons-fullwidth">
                    <div class="control">
                        <input class="input" type="text" name="path" placeholder="Path to project" required>
                    </div>
                    <button class="button is-danger">
                        Process
                    </button>
                </div>
            </form>
            <div id="odvNyanCat" class="NyanCat NyanSize is-hidden"></div>
            <audio preload muted>
                <source src="{{ URL::asset('media/nyancat.ogg') }}" type="audio/ogg">
            </audio>
        </div>
    </div>
</section>
<section class="hero is-highlight is-medium is-hidden" id="ostResultChangeList">
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
        let audio = document.getElementsByTagName("audio")[0];
        audio.loop = true;
        audio.volume = 0.5;
        async function FSxRENChangeListTable(ptActionUrl, ptPath) {
            const oTableController = document.querySelector('#odvChangeListTable1');
            const oTableModel = document.querySelector('#odvChangeListTable2');
            oTableController.innerHTML = '';
            oTableModel.innerHTML = '';
            const oResponse = await fetch(ptActionUrl,{
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    path: ptPath
                })
            });
            const oData = await oResponse.json();
            if(oData.error) {
                console.error(oData);
                alert('เกิดข้อผิดพลาด ! \nReason: ' + oData.error);
                return;
            }
            if(oData.success) {
                document.querySelector('#ostResultChangeList').classList.remove('is-hidden');
                oTableController.innerHTML = '<h1 class="title has-text-white">Controller</h1>';
                oTableController.innerHTML += '<table class="table is-bordered is-striped is-fullwidth">\n' +
                    '                        <thead>\n' +
                    '                        <tr>\n' +
                    '                            <th class="has-text-centered">Old Name</th>\n' +
                    '                            <th class="has-text-centered">New Name</th>\n' +
                    '                        </tr>\n' +
                    '                        </thead>\n' +
                    '                        <tbody>\n' +
                    '                        </tbody>\n' +
                    '                    </table>';
                oData.success.controller.forEach((item) => {
                    oTableController.querySelector('tbody').innerHTML += '<tr>\n' +
                        '                            <td>' + item.originalName + '</td>\n' +
                        '                            <td>' + item.newName + '</td>\n' +
                        '                        </tr>';
                });

                oTableModel.innerHTML = '<h1 class="title has-text-white">Model</h1>';
                oTableModel.innerHTML += '<table class="table is-bordered is-striped is-fullwidth">\n' +
                    '                        <thead>\n' +
                    '                        <tr>\n' +
                    '                            <th class="has-text-centered">Old Name</th>\n' +
                    '                            <th class="has-text-centered">New Name</th>\n' +
                    '                        </tr>\n' +
                    '                        </thead>\n' +
                    '                        <tbody>\n' +
                    '                        </tbody>\n' +
                    '                    </table>';
                oData.success.model.forEach((item) => {
                    oTableModel.querySelector('tbody').innerHTML += '<tr>\n' +
                        '                            <td>' + item.originalName + '</td>\n' +
                        '                            <td>' + item.newName + '</td>\n' +
                        '                        </tr>';
                });
            }
        }

        document.querySelector('#ofmRenameForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            audio.muted = false;
            await audio.play();
            document.querySelector('#odvNyanCat').classList.remove('is-hidden');
            this.querySelector('button').classList.add('is-loading');
            const tPath = document.querySelector('input[name="path"]').value;
            const tActionUrl = e.target.action;
            await FSxRENChangeListTable(tActionUrl, tPath);
            this.querySelector('button').classList.remove('is-loading');
            document.querySelector('#odvNyanCat').classList.add('is-hidden');
            await audio.pause();
        });
    </script>
@endpush

