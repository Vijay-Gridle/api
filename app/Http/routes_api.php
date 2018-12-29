<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

use App\Questions;
use App\Users;
use App\Votes;
use App\Comments;
use Illuminate\Http\Request;

Route::group(['middleware' => ['api']], function () {
 

Route::get('/questions', function () {
        $all_details = Questions::where('type', '0')->orderBy('created_at', 'asc')->get();
        $main_arr=array();
        if(!empty($all_details)){
          foreach ($all_details as $key => $all_detail) {
            $user_details = isset($all_detail->user_id)?Users::where('id',$all_detail->user_id)->get()->first():array();
            $answer_details = isset($all_detail->que_id)?Questions::where('que_id',$all_detail->id)->where('type','1')->get():array();
            $all_detail->ques_vote = isset($all_detail->que_id)?Votes::where('qus_id',$all_detail->id)->count('user_id'):array();
           
            $all_detail->user_name = isset($user_details->name)?$user_details->name:'';
            $all_detail->email = isset($user_details->email)?$user_details->email:'';
             $answer = array();
            if(!empty($answer_details)){
              foreach ($answer_details as $key => $answer_detail) {
                $ans_vote = isset($all_detail->que_id)?Votes::where('qus_id',$answer_detail->id)->count('user_id'):0;
                $comment_details = isset($answer_detail->id)? Comments::where('ans_id',$answer_detail->id)->get():array();
                $comments_arr=array();
                if(!empty($comment_details)){
                   foreach ($comment_details as $key => $comment_detail) {
                    $user_com_details = isset($comment_detail->user_id)?Users::where('id',$comment_detail->user_id)->get()->first():array();
                    $comments_arr[] = array('user_name'=>isset($user_com_details->name)?$user_com_details->name:'','email'=> isset($user_com_details->email)?$user_com_details->email:'','desc'=> isset($comment_detail->desc)?$comment_detail->desc:'');

                   }
                }
                $user_ans_details = isset($answer_detail->user_id)?Users::where('id',$answer_detail->user_id)->get()->first():array();
                $answer[] = array('user_name'=>isset($user_ans_details->name)?$user_ans_details->name:'','email'=> isset($user_ans_details->email)?$user_ans_details->email:'','desc'=> isset($answer_detail->desc)?$answer_detail->desc:'','all_comments'=> $comments_arr,'vote'=>$ans_vote);
              }
            }
            $all_detail->all_answer = $answer;

            $main_arr[] = $all_detail;
          }
          
        }
        if(!empty($main_arr)){
       $results['status'] ='success';
       $results['msg'] = 'get all datas.';
       $results['data'] = $main_arr;
       }else{
       $results['status'] ='error';
       $results['msg'] = 'No data found!';
       }
       echo json_encode($results); exit();
    });


/**
     * Post request for Question or Answer for save
     */
 Route::post('/users', function (Request $request) {
        $results =array();
        $validator = Validator::make($request->all(),['name' => 'required|max:255','email'=>'required|email','password'=>'required|min:8']);
        if ($validator->fails()) {
                 $results['status'] ='error';
                 $results['msg'] = $validator->errors()->add('error_input', '');
                 echo json_encode($results); exit();
        }
        
        $users = new Users;
        $users->name = request('name');
        $users->email = request('email');
        $users->password =request('password');

        $user_details = Users::where('email', $users->email)->get()->first();
        if(!empty($user_details)){
           $results['status'] ='error';
           $results['msg'] = 'user already exist!';
           echo json_encode($results); exit();
        }
        $users->save();
        $results['status'] ='success';
        $results['msg'] = 'User has been successfully added';
       echo json_encode($results); exit();
    });

  /**
     * Post request for Question or Answer for save
     */
 Route::post('/questions', function (Request $request) {
        $results =array();
        $validator = Validator::make($request->all(),['title' => 'required|max:255','type'=>'required','desc'=>'required','que_id'=>'required']);
        if ($validator->fails()) {
                 $results['status'] ='error';
                 $results['msg'] = $validator->errors()->add('error_input', '');
                 echo json_encode($results); exit();
        }
        
        $question = new Questions;
        $question->user_id = request('user_id');
        $question->title = request('title');
        $question->type = request('type');
        $question->desc = request('desc');
        $question->que_id = request('que_id');

        $user_details = Users::where('id', $question->user_id)->get()->first();
        if(empty($user_details)){
           $results['status'] ='error';
           $results['msg'] = 'No user found!';
           echo json_encode($results); exit();
        }

      if($question->type == '1'){
        $que_details = Questions::where('id', $question->que_id)->get()->first();
        if(empty($que_details)){
           $results['status'] ='error';
           $results['msg'] = 'No question found!';
           echo json_encode($results); exit();
        }
      }
        $question->save();
        $results['status'] ='success';
        if($question->type == '0'){
          $results['msg'] = 'Question has been successfully added';
        }else{

          $results['msg'] = 'Answer has been successfully added';
        }
       echo json_encode($results); exit();
    });
    

    /**
     * Delete Question
     */
    Route::delete('/questions/{id}', function ($id) {
       $results=array();
        Votes::where('qus_id', $id)->delete();
        Comments::where('ans_id', $id)->delete();
        Questions::where('que_id', $id)->delete();
         $details = Questions::where('id', $id)->delete();
        if($details){
          $results['status'] ='success';
         $results['msg'] = 'deleted successfully.';
        }else{
          $results['status'] ='error';
         $results['msg'] = 'Something went wrong!'; 
        }
       echo json_encode($results); exit();
    });

/**
     * Post request for answer comment
     */
 Route::post('/comments', function (Request $request) {
        $results =array();
        $validator = Validator::make($request->all(),['desc' => 'required|max:255','ans_id'=>'required','user_id'=>'required']);
        if ($validator->fails()) {
                 $results['status'] ='error';
                 $results['msg'] = $validator->errors()->add('error_input', '');
                 echo json_encode($results); exit();
        }
        
        $comments = new Comments;
        $comments->desc = request('desc');
        $comments->ans_id = request('ans_id');
        $comments->user_id = request('user_id');

        $user_details = Users::where('id', $comments->user_id)->get()->first();
        if(empty($user_details)){
           $results['status'] ='error';
           $results['msg'] = 'No user found!';
           echo json_encode($results); exit();
        }
     
        $que_details = Questions::where('id', $comments->ans_id)->get()->first();
        if(empty($que_details)){
           $results['status'] ='error';
           $results['msg'] = 'No answer found!';
           echo json_encode($results); exit();
        }else if(isset($que_details->type) && $que_details->type == '0'){
           $results['status'] ='error';
           $results['msg'] = 'No answer found!';
           echo json_encode($results); exit();
        }

        $comments->save();
        $results['status'] ='success';
        $results['msg'] = 'Comment has been successfully added';
       echo json_encode($results); exit();
    });

    /**
     * Post request for votes for Upvote or Downvote
     */
 Route::post('/votes', function (Request $request) {
        $results =array();
        $validator = Validator::make($request->all(),['user_id' => 'required','qus_id'=>'required']);
        if ($validator->fails()) {
                 $results['status'] ='error';
                 $results['msg'] = $validator->errors()->add('error_input', '');
                 echo json_encode($results); exit();
        }

        $vote = new Votes;
        $vote->user_id = request('user_id');
        $vote->qus_id = request('qus_id');

        $user_details = Users::where('id', $vote->user_id)->get()->first();
        if(empty($user_details)){
           $results['status'] ='error';
           $results['msg'] = 'No user found for vote!';
           echo json_encode($results); exit();
        }
     
        $que_details = Questions::where('id', $vote->qus_id)->get()->first();
        if(empty($que_details)){
           $results['status'] ='error';
           $results['msg'] = 'No question found for vote!';
           echo json_encode($results); exit();
        }
    
          $user_details = Votes::where('user_id', $vote->user_id)->where('qus_id', $vote->qus_id)->get()->first();
          if(!empty($user_details)){
             Votes::where('id', $user_details->id)->delete();
             $results['status'] ='success';
             $results['msg'] = 'Downvote successfully done.';
          }else{
             $vote->save();
             $results['msg'] = 'Upvote successfully done.';
              $results['status'] ='success';
           }
         echo json_encode($results); exit();
    });
    
});

