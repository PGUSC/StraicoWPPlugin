# Straico API Documentation 

## General API Information

The Straico API utilizes API keys for authentication. 

**Important Security Note:** Keep your API key confidential. Do not share it or embed it in client-side code. All production requests should be made through your backend server, where the API key can be securely fetched from an environment variable or a key management service.

**Authentication Header:**
Every API request must include your API key in an `Authorization` HTTP header:
`Authorization: Bearer $STRAICO_API_KEY`
(Replace `$STRAICO_API_KEY` with your actual API key.)

---

## User Endpoints

### User information

**Purpose:**
This endpoint allows users to fetch details of a specific user from the Straico platform. Users can access information such as the user's first name, last name, the number of coins associated with the account, and the plan they are subscribed to.

**Endpoint:** `GET https://api.straico.com/v0/user`

**Authentication:**
As per general API information (Bearer Token).

**Request Headers:**
- `Authorization: Bearer $STRAICO_API_KEY`

**Request Body:**
This endpoint does not require any parameters in the request body.

**Response:**
The API responds with a JSON object containing the user's data under the "data" key.

| Field      | Type   | Description                                 |
|------------|--------|---------------------------------------------|
| `first_name` | string | The user's first name                       |
| `last_name`  | string | The user's last name                        |
| `coins`      | number | The number of coins associated with the account |
| `plan`       | string | The current subscription plan of the user   |

**Example Response Body:**
```json
{
    "data": {
        "first_name": "Jane",
        "last_name": "Doe",
        "coins": 562621.19,
        "plan": "Ultimate Pack"
    },
    "success": true
}
```

---

## Model Endpoints

### Models information (v.1)

**Purpose:**
The models information endpoint (v.1) allows users to fetch a list of available models, categorized by type (chat and image generation), along with their corresponding details from the Straico API. This version offers detailed specifications and pricing for each category.

**Endpoint:** `GET https://api.straico.com/v1/models`

**Authentication:**
As per general API information (Bearer Token).

**Request Headers:**
- `Authorization: Bearer $STRAICO_API_KEY`

**Request Body:**
This endpoint does not require any parameters.

**Response:**
Upon a successful request, the API responds with a JSON object containing categorized arrays of model objects. The response includes separate arrays for `chat` models and `image` generation models under a "data" key.

**Response Fields (under "data"):**
| Field   | Type  | Description                             |
|---------|-------|-----------------------------------------|
| `chat`  | array | Array of chat model objects                                   |
| `image` | array | Array containing a single nested array of image model objects |

**Chat Model Object Fields:**
| Field        | Type   | Description                                                                        |
|--------------|--------|------------------------------------------------------------------------------------|
| `name`       | string | Model display name                                                                 |
| `model`      | string | Unique model identifier for API usage                                              |
| `word_limit` | number | Maximum number of words the model can process                                      |
| `pricing`    | object | Pricing information (e.g., `{"coins": 1, "words": 100}`)                            |
| `max_output` | number | Limit number of tokens (roughly words or word pieces) that can be generated in a single response |
| `metadata`   | object | Additional model metadata including `icon`, `pros`, `cons`, etc.                   |


**Image Model Object Fields (within the nested array `data.image[0]`):**
| Field       | Type   | Description                                                                 |
|-------------|--------|-----------------------------------------------------------------------------|
| `_id`       | string | Internal database ID for the model.                                         |
| `name`      | string | Model display name (e.g., "OpenAI: Dall-E 3").                              |
| `model`     | string | Unique model identifier for API usage (e.g., "openai/dall-e-3").            |
| `pricing`   | object | Pricing information by size. Each size key (e.g., `square`, `landscape`, `portrait`) contains an object with `coins` (number) and `size` (string, e.g., "1024x1024"). |

**Example `chat` model entry in response:**
```json
{
    "name": "Anthropic: Claude 3 Haiku",
    "model": "anthropic/claude-3-haiku:beta",
    "word_limit": 150000,
    "pricing": {
        "coins": 1,
        "words": 100
    },
    "max_output": 4096,
    "metadata": {
        "editors_link": "",
        "editors_choice_level": -1,
        "cons": [
            "Refuses to interact with some copywritten work"
        ],
        "pros": [
            "Concise humor",
            "Strong understanding of creativity",
            "Outputs longer than previous generations"
        ],
        "applications": [
            "Social chat",
            "Content"
        ],
        "capabilities": [
            "Browsing",
            "Image generation"
        ],
        "features": [],
        "other": [],
        "icon": "https://prompt-rack.s3.us-east-1.amazonaws.com/model-icons/claude-3-opus.png"
    }
}
```
**Example `image` model entry in response (from `data.image[0]` array):**
```json
{
    "pricing": {
        "square": {
            "coins": 90,
            "size": "1024x1024"
        },
        "landscape": {
            "coins": 120,
            "size": "1792x1024"
        },
        "portrait": {
            "coins": 120,
            "size": "1024x1792"
        }
    },
    "_id": "67e08e044e0a3624a03ab154",
    "name": "OpenAI: Dall-E 3",
    "model": "openai/dall-e-3"
}
```

**Full Example Response Structure for `v1/models`:**
```json
{
    "data": {
        "chat": [
            {
                "name": "Amazon: Nova Lite 1.0",
                "model": "amazon/nova-lite-v1",
                "word_limit": 225000,
                "pricing": {
                    "coins": 0.2,
                    "words": 100
                },
                "max_output": 5000,
                "metadata": {
                    "editors_link": "",
                    "editors_choice_level": -1,
                    "cons": [
                        "May not perform strongly on deeply complex textual inference or long-chain reasoning tasks"
                    ],
                    "pros": [
                        "Very low cost and lightning-fast multimodal processing"
                    ],
                    "applications": [
                        "Tutoring",
                        "Social chat",
                        "Writing"
                    ],
                    "capabilities": [],
                    "features": [
                        "Image input"
                    ],
                    "other": [],
                    "icon": "https://prompt-rack.s3.us-east-1.amazonaws.com/model-icons/nova-lite-1.0.png"
                }
            }
            // ... more chat models
        ],
        "image": [
            [ // Note the nested array for image models
                {
                    "pricing": {
                        "square": {
                            "coins": 90,
                            "size": "1024x1024"
                        },
                        "landscape": {
                            "coins": 120,
                            "size": "1792x1024"
                        },
                        "portrait": {
                            "coins": 120,
                            "size": "1024x1792"
                        }
                    },
                    "_id": "67e08e044e0a3624a03ab154",
                    "name": "OpenAI: Dall-E 3",
                    "model": "openai/dall-e-3"
                }
                // ... more image models
            ]
        ]
    },
    "success": true
}
```

---

## Prompt Endpoints

### Prompt completion (v.1)

**Purpose:**
The Prompt Completions endpoint (v.1) enables users to generate prompt completions using multiple language models (LLMs) simultaneously (up to 4 different models in a single request). This version supports the inclusion of YouTube video files and various attachments.

**Endpoint:** `POST https://api.straico.com/v1/prompt/completion`

**Authentication:**
As per general API information (Bearer Token).

**Request Headers:**
- `Authorization: Bearer $STRAICO_API_KEY`
- `Content-Type: application/json`

**Request Body (JSON):**
Files must be pre-uploaded using the File Upload endpoint, which provides valid URLs for use in this request.

| Parameter             | Type    | Required | Description                                                                              |
|-----------------------|---------|----------|------------------------------------------------------------------------------------------|
| `models`              | array   | Yes      | An array of 1-4 unique model identifiers (strings).                                      |
| `message`             | string  | Yes      | The prompt text for which completions are requested.                                     |
| `file_urls`           | array   | No       | An array of up to 4 file URLs (strings), previously uploaded via the File Upload endpoint. |
| `images`              | array   | No       | An array of image URLs (strings).                                                        |
| `youtube_urls`        | array   | No       | An array of up to 4 YouTube video URLs (strings).                                        |
| `display_transcripts` | boolean | No       | If `true`, returns transcripts of the files. Default: `false`.                           |
| `temperature`         | number  | No       | Influences variety in model's responses (0-2).                                           |
| `max_tokens`          | number  | No       | Sets the limit for tokens generated in response.                                         |

**Response:**
The API responds with a JSON object containing the following information:

| Field             | Type   | Description                                                                                                 |
|-------------------|--------|-------------------------------------------------------------------------------------------------------------|
| `overall_price`   | object | Overall cost for all models (contains `input`, `output`, `total` coins).                                     |
| `overall_words`   | object | Overall word count for all models (contains `input`, `output`, `total` words).                               |
| `completions`     | object | Detailed results for each requested model. Each key is a model identifier, and value is its completion data. |
| `transcripts`     | array  | If `display_transcripts` is `true`, an array of transcripts (each with `name` and `text`).                  |

**Completion Object (for each model within `completions`):**
This typically includes sub-objects for `completion` (with choices, message content, usage tokens), `price`, and `words`.

**Example Request Body:**
```json
{
    "models": [
        "anthropic/claude-3.7-sonnet:thinking",
        "meta-llama/llama-4-maverick"
    ],
    "message": "Which key terms from the 100-word space document also appear in the YouTube video about the universe?. What relation do you find between the two sources and the image attached?",
    "file_urls": [
        "https://prompt-rack.s3.amazonaws.com/api/1721329178731_space_words.csv"
    ],
    "youtube_urls": [
        "https://www.youtube.com/watch?v=zORUUqJd81M"
    ],
    "images": [
        "https://prompt-rack.s3.amazonaws.com/api/1744755154982_galaxy.jpg"
    ]
}
```

---

## File Endpoints

### File upload

**Purpose:**
This endpoint allows users to upload files for use in the Prompt Completion (v.1) endpoint. Files are securely stored in cloud storage.

**Endpoint:** `POST https://api.straico.com/v0/file/upload`

**Authentication:**
As per general API information (Bearer Token).

**Request Headers:**
- `Authorization: Bearer $STRAICO_API_KEY`
- `Content-Type: multipart/form-data`

**Request Body (`multipart/form-data`):**
| Parameter | Type | Required | Description             |
|-----------|------|----------|-------------------------|
| `file`    | File | Yes      | The file to be uploaded. |

**Constraints:**
- Maximum file size: 25 MB.
- Supported file types: pdf, docx, pptx, txt, xlsx, mp3, mp4, html, csv, json, py, php, js, css, cs, swift, kt, xml, ts, png, jpg, jpeg, webp, gif.

**Response:**
The API responds with a JSON object containing:

| Field | Type   | Description                                  |
|-------|--------|----------------------------------------------|
| `url` | string | The URL of the successfully uploaded file. |

**Example Response Body:**
```json
{
    "data": {
        "url": "https://prompt-rack.s3.amazonaws.com/api/1721329178731_space_words.csv"
    },
    "success": true
}
```

---

## Image Endpoints

### Image generation

**Purpose:**
This endpoint enables users to generate high-quality images based on textual descriptions using advanced AI models.

**Endpoint:** `POST https://api.straico.com/v0/image/generation`

**Authentication:**
As per general API information (Bearer Token).

**Request Headers:**
- `Authorization: Bearer $STRAICO_API_KEY`
- `Content-Type: application/json`

**Request Body (JSON):**
| Parameter     | Type    | Required | Description                                                                                                                                                                            |
|---------------|---------|----------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `model`       | string  | Yes      | The AI model to use. Available: `openai/dall-e-3`, `flux/1.1`, `ideogram/V_2A`, `ideogram/V_2A_TURBO`, `ideogram/V_2`, `ideogram/V_2_TURBO`, `ideogram/V_1`, `ideogram/V_1_TURBO`.    |
| `description` | string  | Yes      | A detailed textual description of the image to be generated.                                                                                                                           |
| `size`        | string  | Yes      | Desired image dimensions. Options: `square`, `landscape`, `portrait`.                                                                                                                  |
| `variations`  | integer | Yes      | Number of images to generate. Minimum: 1, Maximum: 4.                                                                                                                                |
| `seed`        | integer | No       | Applies only to flux and ideogram models. Controls result variability. Value between 0 and 2,147,483,647. Same seed, prompt, and model version yield the same image.                    |

**Response:**
The API responds with a JSON object containing:

| Field  | Type   | Description                                                                 |
|--------|--------|-----------------------------------------------------------------------------|
| `zip`  | string | URL to download a ZIP file containing all generated images.                 |
| `images` | array  | Array of URLs (strings), each pointing to an individual generated image.  |
| `price`  | object | Detailed pricing: `price_per_image`, `quantity_images`, and `total`.      |

**Example Request Body:**
```json
{
    "model": "openai/dall-e-3",
    "description": "A stunning depiction of the Milky Way galaxy alongside the Andromeda galaxy",
    "size": "landscape",
    "variations": 2
}
```
**Example Response Body:**
```json
{
    "data": {
        "zip": "https://prompt-rack.s3.amazonaws.com/api/1721333310153_e8gn2Z4K.zip",
        "images": [
            "https://prompt-rack.s3.amazonaws.com/api/1721333307376_bSyyTpYn.png",
            "https://prompt-rack.s3.amazonaws.com/api/1721333308709_9kVx2vm9.png"
        ],
        "price": {
            "price_per_image": 120,
            "quantity_images": 2,
            "total": 240
        }
    },
    "success": true
}
```

---

## Agents Endpoints
This is the first version of the documentation for agent-related API endpoints, allowing users to manage agents and utilize Retrieval-Augmented Generation (RAG) capabilities.

### Create Agent

**Purpose:** Creates a new agent in the database for the user.

**Endpoint:** `POST https://api.straico.com/v0/agent`

**Authentication:** Bearer Token.

**Request Headers:**
- `Authorization: Bearer <your-token>`
- `Content-Type: application/x-www-form-urlencoded` (implicitly, based on body type)

**Request Body (`urlencoded`):**
- `name` (text): A name for the agent. (Required)
- `custom_prompt` (text): A custom prompt for the agent's behavior. (Required)
- `default_llm` (text): A model identifier that the agent will use (e.g., "anthropic/claude-3.5-sonnet"). (Required)
- `description` (text): A brief description of what the agent does. (Required)
- `tags` (text): An array of tags for the agent (e.g., '["assistant","rag"]'). (Required, format as stringified JSON array)

**Response:**
JSON object containing details of the created agent, including `uuidv4`, `user_id`, `default_llm`, `custom_prompt`, `name`, `description`, `status`, `tags`, `_id`, `createdAt`, `updatedAt`.

### Add RAG to Agent

**Purpose:** Associates an existing RAG base with an agent.

**Endpoint:** `POST https://api.straico.com/v0/agent/<agent-id>/rag`

**Authentication:** Bearer Token.

**Request Headers:**
- `Authorization: Bearer <token>`
- `Content-Type: application/json`

**Request Body (JSON):**
- `rag` (string, Required): The ID of the RAG base to associate (e.g., "670469b677rwtrwwr5b0903").

**Path Parameters:**
- `<agent-id>`: The ID of the agent.

**Response:**
JSON object containing updated details of the agent, similar to "Create Agent" response, now including RAG association.

### Agent details

**Purpose:** Retrieves details of a specific agent.

**Endpoint:** `GET https://api.straico.com/v0/agent/<agent-id>`

**Authentication:** Bearer Token.

**Request Headers:**
- `Authorization: Bearer <token>`

**Path Parameters:**
- `<agent-id>`: The ID of the agent.

**Response:**
JSON object containing agent details, including `_id`, `uuidv4`, `default_llm`, `custom_prompt`, `name`, `description`, `status`, `tags`, `rag_association` (URL to FAISS index), etc.

### List of agents

**Purpose:** Retrieves the list of agents created by and available to the user.

**Endpoint:** `GET https://api.straico.com/v0/agent/`

**Authentication:** Bearer Token.

**Request Headers:**
- `Authorization: Bearer <token>`

**Response:**
A JSON object with a `data` key containing an array of agent objects. Each agent object has fields similar to "Agent details".

### Update agent

**Purpose:** Allows updating the details of a specific agent.

**Endpoint:** `PUT https://api.straico.com/v0/agent/<agent-id>`

**Authentication:** Bearer Token.

**Request Headers:**
- `Authorization: Bearer <token>`
- `Content-Type: application/x-www-form-urlencoded` (implicitly, based on body type)

**Path Parameters:**
- `<agent-id>`: The ID of the agent to update.

**Request Body (`urlencoded`):**
You can update various attributes of the agent.
- `name` (text, Optional): New value for the agent's name.
- `<any other attribute>` (text, Optional): New value for other updatable attributes (e.g., `custom_prompt`, `default_llm`).

**Response:**
JSON object containing the updated agent details.

### Agent prompt completion

**Purpose:** Submits a prompt to an agent for completion, using its default model and associated RAG base if any.

**Endpoint:** `POST https://api.straico.com/v0/agent/<agent-id>/prompt`

**Authentication:** Bearer Token.

**Request Headers:**
- `Authorization: Bearer <token>`
- `Content-Type: application/x-www-form-urlencoded` (implicitly, based on body type)

**Path Parameters:**
- `<agent-id>`: The ID of the agent.

**Request Body (`urlencoded`):**
- `prompt` (text, Required): The text prompt for the agent.

**Optional FAISS Retriever Parameters (also via `urlencoded`):**
- `search_type` (text): `similarity`, `mmr`, or `similarity_score_threshold`.
- `k` (number): Number of documents to retrieve.
- `fetch_k` (number): Number of documents to pass to MMR algorithm.
- `lambda_mult` (number): Diversity for MMR (0 for max diversity, 1 for min).
- `score_threshold` (number): Min relevance for `similarity_score_threshold`.

**Response:**
JSON object containing:
- `answer` (string): The agent's response.
- `references` (array): Array of source document snippets used for RAG, each with `page_content` and `page`.
- `file_name` (string): Name of the source file for references.
- `coins_used` (number): Cost of the operation.
(Note: The response might also have a legacy `response` field mirroring the `data` object.)

### Delete agent

**Purpose:** Deletes a specific agent.

**Endpoint:** `DELETE https://api.straico.com/v0/agent/<agent-id>`

**Authentication:** Bearer Token.

**Request Headers:**
- `Authorization: Bearer <token>`

**Path Parameters:**
- `<agent-id>`: The ID of the agent to delete.

**Request Body:**
This request does not require a request body.

**Response:**
```json
{
    "success": true,
    "message": "Agent deleted successfully"
}
```

---

## RAG (Retrieval-Augmented Generation) Endpoints

### Create RAG

**Purpose:** Creates a new RAG base from uploaded files.

**Endpoint:** `POST https://api.straico.com/v0/rag`

**Authentication:** Bearer Token.

**Request Headers:**
- `Authorization: Bearer <token>`
- `Content-Type: multipart/form-data` (implicitly, from body type)

**Request Body (`form-data`):**
- `name` (text, Required): Name for the RAG base.
- `description` (text, Required): Description of the RAG base.
- `files` (file, Required): Files to be attached (up to 4). Accepted extensions: pdf, docx, csv, txt, xlsx, py.
- `chunking_method` (text, Optional): Method for chunking text. Default: `fixed_size`.
    | `chunking_method` | Available optional parameters                                                                     | Should perform quite well with | Cons                                             |
    |-------------------|---------------------------------------------------------------------------------------------------|--------------------------------|--------------------------------------------------|
    | `fixed_size`      | `chunk_size` (default: 1000), `chunk_overlap` (default: 50), `separator` (default: \\n)          | Not so elaborated text         | Does not consider text structure and semantics   |
    | `recursive`       | `chunk_size` (default: 1000), `chunk_overlap` (default: 50), `separators` (default: ["\\n\\n", "\\n", " ", ""]) | Not so elaborated text         | Does not consider text structure and semantics   |
    | `markdown`        | `chunk_size` (default: 1000), `chunk_overlap` (default: 50)                                     | Files with Markdown          | Only suitable for files with Markdown            |
    | `python`          | `chunk_size` (default: 1000), `chunk_overlap` (default: 50)                                     | Python files                   | Only suitable for Python Files                   |
    | `semantic`        | `breakpoint_threshold_type` (values: `percentile, interquartile, standard_deviation, gradient`), `buffer_size` (default: 100) | Significantly elaborated texts | Very slow, compared to other methods           |

**Response (Status: 201):**
JSON object with `success`, `data` (containing `user_id`, `name`, `rag_url`, `original_filename`, `chunking_method`, `chunk_size`, `chunk_overlap`, `_id`, `createdAt`, `updatedAt`), `total_coins`, `total_words`.
**Notes on coins and processing time:**
- Current fee: 0.1 coins / 100 words of processed files.
- Processing time: Approx. 1 minute per 50,000 words.

### List of RAGs

**Purpose:** Returns the list of RAG bases for the user.

**Endpoint:** `GET https://api.straico.com/v0/rag/user`

**Authentication:** Bearer Token.

**Request Headers:**
- `Authorization: Bearer <token>`

**Response:**
JSON object with `success` and `data` (an array of RAG objects). Each RAG object includes:
`_id`, `user_id`, `name`, `rag_url`, `original_filename`, `chunking_method`, `chunk_size`, `chunk_overlap`, `createdAt`, `updatedAt`.

**Example RAG object in response array:**
```json
{
    "_id": "670558840d774c63b439",
    "user_id": "64ada93ff7a131d3f5",
    "name": "Test",
    "rag_url": "https://prompt-rack.s3.amazonaws.com/api/rag/64ada93ff7a1d6822131d3f5/898555bd-7701-473f-9ffe-f02303a2cc59/index.faiss",
    "original_filename": "sample_txt.txt, sample_excel.xlsx, 2000_codigopenal_colombia.pdf",
    "chunking_method": "fixed_size",
    "chunk_size": 1000,
    "chunk_overlap": 50,
    "createdAt": "2024-10-08T16:06:28.210Z",
    "updatedAt": "2024-10-08T16:06:28.210Z",
    "__v": 0
}
```

### RAG by ID (Get RAG Details)

**Purpose:** Retrieves details of a specific RAG base identified by its ID.

**Endpoint:** `GET https://api.straico.com/v0/rag/<rag-id>`

**Authentication:** Bearer Token.

**Request Headers:**
- `Authorization: Bearer <token>`

**Path Parameters:**
- `<rag-id>` (Required): The ID of the RAG base.

**Response:**
JSON object with `success` and `data` (a single RAG object with fields similar to those in "List of RAGs").

**Example Response Data:**
```json
{
    "_id": "670565d02fc07e1234eb",
    "user_id": "64ada922131d3f5",
    "name": "Test",
    "rag_url": "https://prompt-rack.s3.amazonaws.com/api/rag/64ada93ff7a1d6822131d3f5/d27eaec1-00df-48cd-a6ef-1b17a78d841c/index.faiss",
    "original_filename": "sample_txt.txt",
    "chunking_method": "fixed_size",
    "chunk_size": 1000,
    "chunk_overlap": 50,
    "createdAt": "2024-10-08T17:03:12.078Z",
    "updatedAt": "2024-10-08T17:03:12.078Z",
    "__v": 0
}
```

### Update RAG

**Purpose:** Updates an existing RAG base by adding new files to it.

**Endpoint:** `PUT https://api.straico.com/v0/rag/<rag-id>`

**Authentication:** Bearer Token.

**Request Headers:**
- `Authorization: Bearer <token>`
- `Content-Type: multipart/form-data` (implicitly, as per `formdata` in description)

**Path Parameters:**
- `<rag-id>` (Required): The ID of the RAG base to update.

**Request Body (`formdata`):**
- `files` (file/text, Required): The files to be processed and added to the existing RAG base. (The JSON shows `type: "text"` for files in this formdata, which might indicate it expects URLs or similar, but typically for uploads it's `type: "file"` like in create. Clarification might be needed from API dev, but going by JSON description.)

**Response:**
JSON object with `success`, `data` (updated RAG object details), `total_coins`, `total_words` reflecting the update. The `data` object includes fields like `_id`, `user_id`, `name`, `description`, `rag_url`, `original_filename` (which will now include the new files), `chunking_method` parameters, `createdAt`, `updatedAt`.

**Example Response Data (structure based on description):**
```json
{
    "_id": "34123412341234s4ae",
    "user_id": "jeqjreqwez34123",
    "name": "A RAG to be updated",
    "description": "Testing a RAG base to be updated later",
    "rag_url": "https://prompt-rack.s3.amazonaws.com/api/rag/64ada93ff7a1d6822131d3f5/c9eeqebe-5529-4180-31341-037b712e633/index.faiss",
    "original_filename": "contract_of_carriage.pdf, ai.pdf, ONTRACK.pdf, ONTRACK.pdf, ai.pdf, ai.pdf, ai.pdf",
    "chunking_method": "fixed_size",
    "chunk_size": 1000,
    "chunk_overlap": 50,
    "buffer_size": 100,
    "breakpoint_threshold_type": "percentile",
    "separator": "\\n",
    "separators": [
        "\\n\\n",
        "\\n",
        " ",
        ""
    ],
    "createdAt": "2025-01-29T10:00:19.582Z",
    "updatedAt": "2025-01-31T02:23:36.689Z",
    "__v": 0
}
```

### Delete RAG

**Purpose:** Deletes a specific RAG base.

**Endpoint:** `DELETE https://api.straico.com/v0/rag/<rag-id>`

**Authentication:** Bearer Token.

**Request Headers:**
- `Authorization: Bearer <token>` (example: "Az-umLyoHzuF3evdCxb4QNSejXsLi1ap5EykGhhWThM80GRv18t")

**Path Parameters:**
- `<rag-id>` (Required): The ID of the RAG base to delete.

**Response:**
```json
{
    "success": true,
    "message": "RAG deleted successfully"
}
```

### RAG prompt completion

**Purpose:** Submits a prompt to a specific RAG model for completion, using a specified LLM.

**Endpoint:** `POST https://api.straico.com/v0/rag/<rag-id>/prompt`

**Authentication:** Bearer Token.

**Request Headers:**
- `Authorization: Bearer <token>`
- `Content-Type: application/x-www-form-urlencoded` (implicitly, from body type)

**Path Parameters:**
- `<rag-id>` (Required): The ID of the RAG base.

**Request Body (`urlencoded`):**
- `prompt` (text, Required): The text prompt for the RAG model (e.g., "What happens if the flight is delayed?").
- `model` (text, Required): The specific LLM to be used (e.g., "anthropic/claude-3.5-sonnet").

**Optional FAISS Retriever Parameters (also via `urlencoded`):**
These parameters control the retrieval process from the FAISS vector store.
- `search_type` (text): One of `similarity`, `mmr`, or `similarity_score_threshold`.
- `k` (number): Number of documents to return.
- `fetch_k` (number): Amount of documents to pass to MMR algorithm.
- `lambda_mult` (number): Diversity of results returned by MMR (1 for minimum, 0 for maximum).
- `score_threshold` (number): Minimum relevance threshold for `similarity_score_threshold`.

**Notes on coins:**
- Total coins will be based on the regular fees for each LLM available in Straico.
- **WARNING:** Coins will consider the number of words in the `references`.

**Response:**
JSON object with `success` and `data`. The `data` object contains:
- `answer` (string): The generated answer.
- `references` (array): An array of objects, where each object represents a retrieved document chunk used for context. Each reference includes:
    - `page_content` (string): The text content of the chunk.
    - `page` (number): The page number (if applicable) from the original document.
- `file_name` (string): The name of the file from which references were drawn.
- `coins_used` (number): Total coins used for the operation.
(The response also includes a legacy `response` field which mirrors the `data` object for backward compatibility.)

**Example Reference Object:**
```json
{
    "page_content": "diante tiene matrícula condicional como consecuencia de una sanción \\ndisciplinaria...",
    "page": 73
}
```

---