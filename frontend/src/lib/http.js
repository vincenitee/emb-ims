import axios from "axios";

let csrfToken = null;

const http = axios.create({
    baseURL: `${import.meta.env.VITE_API_BASE_URL}/api`,
    withCredentials: true
})

http.interceptors.request.use((config) => {
    if(csrfToken) config.headers['x-csrf-token'] = csrfToken
    return config
})

http.interceptors.response.use(
    (response) => {
        if (response.headers) {
            saveCsrf(response.headers)
        }
        return response
    },
    (error) => {
        if(error.response) saveCsrf(error.response.headers)
        return Promise.reject(error)
    }
)

function saveCsrf(headers) {
    const token = headers['x-csrf-token'];
    if(token) csrfToken = token;
}


export default http

