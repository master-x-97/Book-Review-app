<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File as FacadesFile;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\File;

class BookController extends Controller
{
    //this method will show books listing page
    public function index(Request $request){
        $books = Book::orderBy('created_at','DESC');
        if(!empty($request->keyword)){
            $books = $books->where('title','like','%'.$request->keyword.'%');

        }
        $books = $books->paginate(10);

        return view('books.list',[
            'books'=> $books
        ]);

    }
    //this method will show create book  page
    public function create(){
        return view("books.create");
    }
    //this method will show book in database 
    public function store( Request $request){
        $rules =  [
            'title'=>'required|min:5',
            'author'=>'required|min:3',
            'status'=>'required',
        ];
        if(!empty($request->image)){
            $rules['image'] = 'image';
        }

        $validator = Validator::make($request->all(),$rules );
        if ($validator->fails()) {
            return redirect()->route('books.create')->withErrors($validator)->withInput();
        }

        //save book in  DB
        $book = new Book();
        $book->title = $request->title;
        $book->description = $request->description;
        $book->author = $request->author;
        $book->status = $request->status;
        $book->save();

        // upload book image here
        if(!empty($request->image)){
            $image = $request->image;
            $ext = $image->getClientOriginalExtension() ;
            $imageName = time().'.'.$ext;
            $image->move( public_path('uploads/books'), $imageName);

            $book->image = $imageName;
            $book->save();

            $manager = new ImageManager(Driver::class);
            $img = $manager->read(public_path('uploads/books/'.$imageName)); 
    
            $img->resize(990); 
            $img->save(public_path('uploads/books/thumb/'.$imageName));
        }

        return redirect()->route('books.index')->with('success','Book added successfully');
    }
    //this method will show edit book page 
    public function edit($id){
        $book = Book::findOrFail($id);
        // dd($book);
        return view('books.edit',[
            'book'=> $book
        ]);

    }
    //this method will update book 
    public function update($id , Request $request){
        $book = Book::findOrFail($id);
        $rules =  [
            'title'=>'required|min:5',
            'author'=>'required|min:3',
            'status'=>'required',
        ];
        if(!empty($request->image)){
            $rules['image'] = 'image';
        }

        $validator = Validator::make($request->all(),$rules );
        if ($validator->fails()) {
            return redirect()->route('books.edit',$book->id)->withErrors($validator)->withInput();
        }

        //Update book in  DB
        $book->title = $request->title;
        $book->description = $request->description;
        $book->author = $request->author;
        $book->status = $request->status;
        $book->save();

        // upload book image here
        if(!empty($request->image)){

            //this will delete old book image from books directory

        File::delete(public_path('uploads/profile/'.$book->image));
        File::delete(public_path('uploads/profile/thumb/'.$book->image));

            $image = $request->image;
            $ext = $image->getClientOriginalExtension() ;
            $imageName = time().'.'.$ext;
            $image->move( public_path('uploads/books'), $imageName);

            $book->image = $imageName;
            $book->save();


            // generate image thumbnail here
            $manager = new ImageManager(Driver::class);
            $img = $manager->read(public_path('uploads/books/'.$imageName)); 
            $img->resize(990); 
            $img->save(public_path('uploads/books/thumb/'.$imageName));
        }

        return redirect()->route('books.index')->with('success','Book updated successfully');

    }
    //this method will delete a book from page
    public function destroy(){

    }

}
