<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{
    //this method will show home page
    public function index( Request $request){
        $books= Book::orderBy("created_at","desc");
        if(!empty($request->keyword)){
            $books= $books->where("title",'like','%'.$request->keyword."%");
        }
        $books=$books->where("status",1)->paginate(8);


        return view('home',[
            'books'=> $books
        ]);
    }
    public function detail($id){    
        $book= Book::findOrFail($id);
        if($book->status == 0){
            abort(404);
        }

        $relatedBooks = Book::where('status',1)->take(3)->where('id','!=',$id)->inRandomOrder()->get();

        // dd($relatedBooks);


        return view('book-detail',[
            'book'=> $book,
            'relatedBooks' => $relatedBooks
        ]);
    }

//this method will save review in db
public function saveReview(Request $request){
    $validator=Validator::make($request->all(),[
        'review' => 'required|min:4',
        'rating' => 'required',
    ]);
    if($validator->fails()){
        return response()->json([
            'status'=> false,
            'error' => $validator->errors(),
            
        ]);
    }
    $contReview = Review::where('user_id',Auth::user()->id)->where('book_id',$request->book_id)->count();

    if($contReview > 0){
        session()->flash('error','you already submitted a review');
        return response()->json([
            'status' => true,
        ]);
    }

        //Apply condition here
        $review =new Review();
        $review->review = $request->review;
        $review->rating = $request->rating;
        $review->user_id = Auth::user()->id;
        $review->book_id = $request->book_id;
        $review->save();

        session()->flash('success','Review submitted successfully');
        return response()->json([
            'status'=> true,
        ]);

    }

}
