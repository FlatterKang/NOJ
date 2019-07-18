@extends('group.settings.common', ['selectedTab' => "member"])

@section('settingsTab')

<style>

    user-card{
        display: flex;
        justify-content: flex-start;
        align-items: center;
        margin-bottom: 2rem;
    }

    user-card user-avatar{
        display: block;
        padding-right:1rem;
    }

    user-card user-avatar img{
        height: 3rem;
        width: 3rem;
        border-radius: 2000px;
        object-fit: cover;
        overflow: hidden;
    }

    user-card user-info{
        display: block;
    }

    user-card user-info p{
        margin-bottom:0;
    }


    .badge-role{
        color:#fff;
        vertical-align: text-bottom;
    }

    .cm-user-name{
        color:rgba(0,0,0,0.93);
    }

    .cm-nick-name{
        color:rgba(0,0,0,0.42);
    }

    empty-container{
        display:block;
        text-align: center;
        margin-bottom: 2rem;
    }

    empty-container i{
        font-size:5rem;
        color:rgba(0,0,0,0.42);
    }

    empty-container p{
        font-size: 1rem;
        color:rgba(0,0,0,0.54);
    }
    .cm-operation{
        cursor: pointer;
    }

    markdown-editor{
        display: block;
    }

    markdown-editor .CodeMirror {
        height: 20rem;
    }

    markdown-editor ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    markdown-editor ::-webkit-scrollbar-thumb {
        background-color: rgba(0, 0, 0, 0.2);
    }

    markdown-editor .editor-toolbar.disabled-for-preview a:not(.no-disable){
        opacity: 0.5;
    }

</style>


        <settings-card>
            <settings-header>
                <h5><i class="MDI marker-check"></i> Permission Management</h5>
            </settings-header>
            <settings-body>
                <div class="row mt-4">
                    @foreach($member_list as $m)
                        @if($m["role"]>0)
                        <div class="col-12 col-md-6 col-lg-4">
                            <user-card id="user-permission-{{$m["uid"]}}">
                                <user-avatar>
                                    <a href="/user/{{$m["uid"]}}"><img src="{{$m["avatar"]}}"></a>
                                </user-avatar>
                                <user-info data-clearance="{{$m["role"]}}" data-rolecolor="{{$m["role_color"]}}">
                                    <p><span class="badge badge-role {{$m["role_color"]}}">{{$m["role_parsed"]}}</span> <span class="cm-user-name">{{$m["name"]}}</span> @if($m["nick_name"])<span class="cm-nick-name">({{$m["nick_name"]}})</span>@endif</p>
                                    <p>
                                        <small><i class="MDI google-circles"></i> {{$m["sub_group"]}}</small>
                                        @if($group_clearance>$m["role"])
                                            <small @if($group_clearance <= $m["role"] + 1) style="display:none" @endif class="wemd-green-text cm-operation clearance-up" onclick="changeMemberClearance({{$m['uid']}},'promote')"><i class="MDI arrow-up-drop-circle-outline"></i> Promote</small>
                                            <small @if($m["role"] <= 1) style="display:none" @endif class="wemd-red-text cm-operation clearance-down" onclick="changeMemberClearance({{$m['uid']}},'demote')"><i class="MDI arrow-down-drop-circle-outline"></i> Demote</small>
                                        @endif
                                    </p>
                                </user-info>
                            </user-card>
                        </div>
                        @endif
                    @endforeach
                </div>
            </settings-body>
        </settings-card>

        <settings-card>

            <settings-header>
                <h5><i class="MDI bullhorn"></i> Group Announcement</h5>
            </settings-header>
            <settings-body>
                <div class="form-group">
                    <label for="noticeTitle" class="bmd-label-floating">Title</label>
                    <input type="text" class="form-control" id="noticeTitle" value='{{$group_notice["title"]}}'>
                </div>
                <div class="form-group">
                    <small class="" style="margin-bottom:10px;font-size:17px;">Content</small>
                    <link rel="stylesheet" href="/static/library/simplemde/dist/simplemde.min.css">
                    <markdown-editor class="mt-3 mb-3">
                        <textarea id="notice_editor"></textarea>
                    </markdown-editor>
                </div>
            </settings-body>
            <settings-footer>
                <button type="button" class="btn btn-primary" id="noticeBtn"><i class="MDI autorenew cm-refreshing d-none"></i> Submit</button>
            </settings-footer>

        </settings-card>

@endsection

@section('additionJS')
@include("js.common.hljsLight")
    <script src="/static/library/jquery-datetimepicker/build/jquery.datetimepicker.full.min.js"></script>
    <script src="/static/js/jquery-ui-sortable.min.js"></script>
    <script type="text/javascript" src="/static/library/simplemde/dist/simplemde.min.js"></script>
    <script type="text/javascript" src="/static/library/marked/marked.min.js"></script>
    <script type="text/javascript" src="/static/library/dompurify/dist/purify.min.js"></script>
    <script>

    var simplemde = new SimpleMDE({
            autosave: {
                enabled: true,
                uniqueId: "notice{{$basic_info["gid"]}}",
                delay: 1000,
            },
            element: $("#notice_editor")[0],
            hideIcons: ["guide", "heading","side-by-side","fullscreen"],
            spellChecker: false,
            tabSize: 4,
            renderingConfig: {
                codeSyntaxHighlighting: true
            },
            previewRender: function (plainText) {
                return marked(plainText, {
                    sanitize: true,
                    sanitizer: DOMPurify.sanitize,
                    highlight: function (code) {
                        return hljs.highlightAuto(code).value;
                    }
                });
            },
            status:false,
            toolbar: [{
                    name: "bold",
                    action: SimpleMDE.toggleBold,
                    className: "MDI format-bold",
                    title: "Bold",
                },
                {
                    name: "italic",
                    action: SimpleMDE.toggleItalic,
                    className: "MDI format-italic",
                    title: "Italic",
                },
                "|",
                {
                    name: "quote",
                    action: SimpleMDE.toggleBlockquote,
                    className: "MDI format-quote",
                    title: "Quote",
                },
                {
                    name: "unordered-list",
                    action: SimpleMDE.toggleUnorderedList,
                    className: "MDI format-list-bulleted",
                    title: "Generic List",
                },
                {
                    name: "ordered-list",
                    action: SimpleMDE.toggleOrderedList,
                    className: "MDI format-list-numbers",
                    title: "Numbered List",
                },
                "|",
                {
                    name: "code",
                    action: SimpleMDE.toggleCodeBlock,
                    className: "MDI code-tags",
                    title: "Create Code",
                },
                {
                    name: "link",
                    action: SimpleMDE.drawLink,
                    className: "MDI link-variant",
                    title: "Insert Link",
                },
                {
                    name: "image",
                    action: SimpleMDE.drawImage,
                    className: "MDI image-area",
                    title: "Insert Image",
                },
                "|",
                {
                    name: "preview",
                    action: SimpleMDE.togglePreview,
                    className: "MDI eye no-disable",
                    title: "Toggle Preview",
                },
            ],
        });
        simplemde.value(`{{$group_notice["content"]}}`)

        hljs.initHighlighting();

        function sortableInit(){
            $("#contestModal tbody").sortable({
                items: "> tr",
                appendTo: "parent",
                helper: "clone"
            });
        }

        let ajaxing = false;

        function approveMember(uid){
            if(ajaxing) return;
            ajaxing=true;
            $.ajax({
                type: 'POST',
                url: '/ajax/group/approveMember',
                data: {
                    gid: {{$basic_info["gid"]}},
                    uid: uid
                },
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }, success: function(result){
                    console.log(result);
                    if (result.ret===200) {
                        $('#member_operate'+uid).html("<span class=\"badge badge-pill badge-success\">Approved</span>");
                    } else {
                        alert(result.desc);
                    }
                    ajaxing=false;
                }, error: function(xhr, type){
                    console.log('Ajax error!');
                    alert("Server Connection Error");
                    ajaxing=false;
                }
            });
        }

        function kickMember(uid) {
            if(ajaxing) return;
            confirm({content:'Are you sure you want to kick this member?',title:'Kick Member'},function (deny) {
                if(!deny)
                    removeMember(uid,'Kicked');
            });
        }

        function removeMember(uid,operation){
            if(ajaxing) return;
            ajaxing=true;
            $.ajax({
                type: 'POST',
                url: '/ajax/group/removeMember',
                data: {
                    gid: {{$basic_info["gid"]}},
                    uid: uid
                },
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }, success: function(result){
                    console.log(result);
                    if (result.ret===200) {
                        $('#member_operate'+uid).html(`<span class=\"badge badge-pill badge-danger\">${operation}</span>`);
                    } else {
                        alert(result.desc);
                    }
                    ajaxing=false;
                }, error: function(xhr, type){
                    console.log('Ajax error!');
                    alert("Server Connection Error");
                    ajaxing=false;
                }
            });
        }

        function changeMemberClearance(uid,action){
            if(ajaxing) return;
            var clearance = $('#user-permission-'+uid+' user-info').attr('data-clearance');
            var role_color = $('#user-permission-'+uid+' user-info').attr('data-rolecolor');

            if(action == 'promote'){
                clearance ++;
            }else if(action == 'demote'){
                clearance --;
            }

            ajaxing=true;
            $.ajax({
                type: 'POST',
                url: '/ajax/group/changeMemberClearance',
                data: {
                    gid: {{$basic_info["gid"]}},
                    uid: uid,
                    permission: clearance
                },
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }, success: function(result){
                    if (result.ret===200) {
                        $('#user-permission-'+uid+' .badge-role').animate({opacity: 0},100,function(){
                            $(this).removeClass(role_color);
                            $(this).addClass(result.data.role_color);
                            $(this).text(result.data.role_parsed);
                            $(this).animate({opacity: 1},200);
                            $('#user-permission-'+uid+' user-info').attr('data-clearance',clearance);
                            $('#user-permission-'+uid+' user-info').attr('data-rolecolor',result.data.role_color);
                            $('#user-permission-'+uid+' .clearance-up').show();
                            $('#user-permission-'+uid+' .clearance-down').show();
                            if(clearance + 1 >= {{$group_clearance}} && action == 'promote'){
                                $('#user-permission-'+uid+' .clearance-up').hide();
                            }
                            if(clearance == 1 && action == 'demote'){
                                $('#user-permission-'+uid+' .clearance-down').hide();
                            }
                        });
                    } else {
                        alert(result.desc);
                    }
                    ajaxing=false;
                }, error: function(xhr, type){
                    console.log('Ajax error while posting to joinGroup!');
                    alert("Server Connection Error");
                    ajaxing=false;
                }
            });
        }

        $('.join-policy-choice').on('click',function(){
            if($('#policy-choice-btn').text().trim() == $(this).text()) return;
            var join_policy = $(this).text();
            var choice = $(this).attr('data-policy');
            $.ajax({
                type: 'POST',
                url: '/ajax/group/changeJoinPolicy',
                data: {
                    gid: {{$basic_info["gid"]}},
                    join_policy: choice
                },
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }, success: function(result){
                    if (result.ret===200) {
                        changeText('#join-policy-display',{
                            text : join_policy,
                        });
                        changeText('#policy-choice-btn',{
                            text : join_policy,
                        });
                    } else {
                        alert(result.desc);
                    }
                    ajaxing=false;
                }, error: function(xhr, type){
                    console.log('Ajax error while posting to joinGroup!');
                    alert("Server Connection Error");
                    ajaxing=false;
                }
            });
        });

        $('#image-file').change(function(){
            var file = $(this).get(0).files[0];

            if(file == undefined){
                changeText('#change-image-tip',{
                    text : 'PLEASE CHOOSE A LOCAL FILE',
                    css : {color:'#f00'}
                });
                return;
            }

            if(file.size/1024 > 1024){
                changeText('#change-image-tip',{
                    text : 'THE SELECTED FILE IS TOO LARGE',
                    css : {color:'#f00'}
                });
                return;
            }

            $(this).addClass('updating');
            var data = new FormData();
            data.append('img',file);
            data.append('gid',{{$basic_info["gid"]}});

            $.ajax({
                type: 'POST',
                url: '/ajax/group/changeGroupImage',
                data: data,
                processData : false,
                contentType : false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }, success: function(result){
                    if (result.ret===200) {
                        changeText('#change-image-tip',{
                            text : 'GROUP IMAGE CHANGE SUCESSFUL',
                            css : {color:'#4caf50'}
                        });
                        $('group-image img').attr('src',result.data);
                        $('.group-image').attr('src',result.data);
                    } else {
                        changeText('#change-image-tip',{
                            text : result.desc,
                            css : {color:'#4caf50'}
                        });
                    }
                    ajaxing=false;
                }, error: function(xhr, type){
                    console.log('Ajax error while posting to joinGroup!');
                    alert("Server Connection Error");
                    ajaxing=false;
                }
            });

            //todo call api

            //read the new url from json and replace the old


        });

        $("#noticeBtn").click(function() {
            if(ajaxing) return;
            else ajaxing=true;
            var noticeTitle = $("#noticeTitle").val();
            var noticeContent = simplemde.value();
            $("#noticeBtn > i").removeClass("d-none");
            $.ajax({
                type: 'POST',
                url: '/ajax/group/createNotice',
                data: {
                    gid:{{$basic_info["gid"]}},
                    title:noticeTitle,
                    content:noticeContent
                },
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }, success: function(ret){
                    if (ret.ret==200) {
                        alert(ret.desc);
                        setTimeout(function(){
                            location.href='/group/{{$basic_info["gid"]}}';
                        },800)
                    } else {
                        alert(ret.desc);
                    }
                    ajaxing=false;
                    $("#noticeBtn > i").addClass("d-none");
                }, error: function(xhr, type){
                    console.log('Ajax error while posting to arrangeContest!');
                    alert("Server Connection Error");
                    ajaxing=false;
                    $("#noticeBtn > i").addClass("d-none");
                }
            });
        });

        $('#group-name').keydown(function(e){
            if(e.keyCode == '13'){
                var name = $(this).val();
                if(name == ''){
                    changeText('#group-name-tip',{
                        text : 'THE NAME OF THE GROUP CANNOT BE EMPTY',
                        css : {color:'#f00'}
                    });
                    return;
                }
                $.ajax({
                    type: 'POST',
                    url: '/ajax/group/changeGroupName',
                    data: {
                        gid: {{$basic_info["gid"]}},
                        group_name: name
                    },
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }, success: function(result){
                        if (result.ret===200) {
                            changeText('#group-name-display',{
                                text : name,
                            });
                            changeText('#group-name-tip',{
                                text : 'GROUP NAME CHANGE SUCESSFUL',
                                css : {color:'#4caf50'}
                            });
                        } else {
                            changeText('#group-name-tip',{
                                text : result.desc,
                                color : '#f00',
                            });
                        }
                        ajaxing=false;
                    }, error: function(xhr, type){
                        console.log('Ajax error while posting to joinGroup!');
                        alert("Server Connection Error");
                        ajaxing=false;
                    }
                });
            }
        });

        $('#problemCode').bind('keypress',function(event){
            if(event.keyCode == "13") {
                addProblem();
            }
        });

        $("#addProblemBtn").click(function() {
            addProblem();
        });

        $("#joinGroup").click(function() {
            if(ajaxing) return;
            ajaxing=true;
            $("#joinGroup > i").removeClass("d-none");
            $.ajax({
                type: 'POST',
                url: '/ajax/joinGroup',
                data: {
                    gid: {{$basic_info["gid"]}}
                },
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }, success: function(result){
                    console.log(result);
                    if (result.ret===200) {
                        $('#joinGroup').html('Waiting').attr('disabled','true');
                    } else {
                        alert(result.desc);
                    }
                    ajaxing=false;
                    $("#joinGroup > i").addClass("d-none");
                }, error: function(xhr, type){
                    console.log('Ajax error while posting to joinGroup!');
                    alert("Server Connection Error");
                    ajaxing=false;
                    $("#joinGroup > i").addClass("d-none");
                }
            });
        });

        $("#changeProfileBtn").click(function() {
            if(ajaxing) return;
            ajaxing=true;
            $("#changeProfileBtn > i").removeClass("d-none");
            $.ajax({
                type: 'POST',
                url: '/ajax/group/changeNickName',
                data: {
                    gid: {{$basic_info["gid"]}},
                    nick_name: $("#nick_name").val()
                },
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }, success: function(ret){
                    console.log(ret);
                    if (ret.ret==200) {
                        location.reload();
                    } else {
                        alert(ret.desc);
                    }
                    ajaxing=false;
                    $("#changeProfileBtn > i").addClass("d-none");
                }, error: function(xhr, type){
                    console.log('Ajax error while posting to changeNickName!');
                    alert("Server Connection Error");
                    ajaxing=false;
                    $("#changeProfileBtn > i").addClass("d-none");
                }
            });
        });


    </script>
@endsection
