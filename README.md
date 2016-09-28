# Keystone Server

Welcome to the Keystone Lumen Rest Server


# Api

[namespaces](/namespaces)



# NOTES

for a keystone GUI:
Redis keeps track of how long a key as NOT been accessed (read)
object idletime $key !!! so don't need to store that in meta

Maybe it would be useful to store the date when a key was created? or updated? both?
Not accessed since we have that with object idletime





# Building APIs You Won't Hate

## Introduction

Think of everything your API will need to handle.  Basically a list of CRUD endpoints.

## Resources

GET /resources - paginated list of stuff
GET /resources/1 - just entity 1
GET /resources/1,2,3 - entity 1 2 and 3 (if multiples allowed)

**Nested**

GET /client/1/connections - list of connects for client 1
GET /connections/5 - connection 5 info (do NOT use client/1/connections/5...)

**Laravel Resources Table**

Verb      | Path                  | Action       | Route Name
----------|-----------------------|--------------|---------------------
GET       | /photo                | index        | photo.index
GET       | /photo/create         | create       | photo.create
POST      | /photo                | store        | photo.store
GET       | /photo/{photo}        | show         | photo.show
GET       | /photo/{photo}/edit   | edit         | photo.edit
PUT/PATCH | /photo/{photo}        | update       | photo.update
DELETE    | /photo/{photo}        | destroy      | photo.destroy

## ID vs UUID

Do not use auto-increment ids, they can be logically assumed and traversed.  So
use GUIDs or UUIDs for all ids - https://github.com/ramsey/uuid or mreschke/helpers/str::getGuid()

## PUT vs POST: Fight!

PUT is used if you know the entire URL adn the action is indempotent (can do it over and over).
Example, a user can have only 1 avatar, and you can upload the avatar over and over and over
PUT /user/1/avatar

POST /user/1/settings would let you post specific fields, no the entire settings array
PUT /user//1/settings would force you to put the ENTIRE settings array

## Plural or Singular or Both

Just use plural for all
/places
/places/59
/places/59,49

## Verb or Noun

NEVER use a verb in a url, the verb is already defined by the HTTP method (get/put/post...)
So NEVER use /getUsers or /sendUserMessage...  use /users or POST /user/5/messages

## Routes and Controllers

Avoid magic routing, just define every route them manually right into a controller

    Route::post('/users', 'UsersController@create');        #Create
    Route::get('/users/{id}', 'UsersController@show');      # Read 1 or multiple users
    Route::put('/users/{id}', 'UsersController@update');    # Update
    Route::delete('/users/{id}', 'UsersController@delete'); # Delete
    Route::get('/users', 'UsersController@list');           # List (all with paging and optional filters)
    Route::get('users/{id}/favorites', 'UsersController@favorites') # List users favorites

Notice favorites is in the UserController, because favorites are relavant to a user...though if you
have something like user/{id}/checkins, you might already have a full CheckinsController, so OK to
put user/checkins there and not in UserControllers.


## Response

Both single and collection returns should be namespaces

    {
        "data": {
            ...
        }
    }

or

    {
        "data": [
            { ... },
            { ... }
        ]
    }

This gives you the ability to add metadata (status, pagination...) as needed.  If you nest data (like comments below)
also use same data namespace

    {
        "data": {
            "name": "Phil Sturgeon",
            "id": "511501255"
            "comments": {
                "data": [
                    {
                        "id": 123423
                        "text": "MongoDB is web-scale!"
                    }
                ]
            }
        }
    }
