<?php

namespace Tests\Misc\App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class BookController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            "title" => "required|string",
            "excerpt" => "required|string",
            "isbn" => "required|numeric",
        ]);

        return redirect()
            ->route("book.index")
            ->withSuccess(__("Book saved."));
    }

    public function update(Request $request, Book $book): RedirectResponse
    {
        return redirect()
            ->route("book.index")
            ->withSuccess(trans("Book updated."));
    }

    public function delete(Book $book): RedirectResponse
    {
        return redirect()
            ->route("book.index")
            ->withSuccess(trans_choice("Deleted :count books.", 1, ["count" => 1]));
    }
}
