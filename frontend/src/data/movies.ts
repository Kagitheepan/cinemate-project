export interface Movie {
    id: string;
    title: string;
    description: string;
    year: string;
    rating: number;
    category: string;
    availableOn: string[];
    cast: { name: string; role: string; imageUrl?: string }[];
    director: string;
    imageUrl?: string;
    duration: number; // Duration in minutes
}

const baseMovies: Movie[] = [
    {
        id: "dune-part-two",
        title: "Dune: Part Two",
        description: "Paul Atreides unites with Chani and the Fremen while seeking revenge against the conspirators who destroyed his family.",
        year: "2024",
        rating: 9.1,
        category: "Science Fiction",
        availableOn: ["Netflix", "Canal+"],
        director: "Denis Villeneuve",
        cast: [
            { name: "Timothée Chalamet", role: "Paul Atreides" },
            { name: "Zendaya", role: "Chani" },
            { name: "Rebecca Ferguson", role: "Lady Jessica" }
        ],
        duration: 166
    },
    {
        id: "everything-everywhere-all-at-once",
        title: "Everything Everywhere All At Once",
        description: "A middle-aged Chinese immigrant is swept up into an insane adventure in which she alone can save the world by exploring other universes connecting with the lives she could have led.",
        year: "2022",
        rating: 8.9,
        category: "Adventure",
        availableOn: ["Amazon Prime"],
        director: "Daniel Kwan, Daniel Scheinert",
        cast: [
            { name: "Michelle Yeoh", role: "Evelyn Wang" },
            { name: "Ke Huy Quan", role: "Waymond Wang" },
            { name: "Stephanie Hsu", role: "Joy Wang" }
        ],
        duration: 139
    },
    {
        id: "oppenheimer",
        title: "Oppenheimer",
        description: "The story of American scientist J. Robert Oppenheimer and his role in the development of the atomic bomb.",
        year: "2023",
        rating: 8.7,
        category: "Biopic",
        availableOn: ["Canal+", "Apple TV"],
        director: "Christopher Nolan",
        cast: [
             { name: "Cillian Murphy", role: "J. Robert Oppenheimer" },
             { name: "Emily Blunt", role: "Kitty Oppenheimer" },
             { name: "Matt Damon", role: "Leslie Groves" }
        ],
        duration: 180
    },
    {
        id: "spider-man-across-the-spider-verse",
        title: "Spider-Man: Across the Spider-Verse",
        description: "Miles Morales catapults across the Multiverse, where he encounters a team of Spider-People charged with protecting its very existence.",
        year: "2023",
        rating: 8.9,
        category: "Animation",
        availableOn: ["Netflix", "Disney+"],
        director: "Joaquim Dos Santos",
        cast: [
            { name: "Shameik Moore", role: "Miles Morales (voice)" },
            { name: "Hailee Steinfeld", role: "Gwen Stacy (voice)" },
            { name: "Oscar Isaac", role: "Miguel O'Hara (voice)" }
        ],
        duration: 140
    },
    {
        id: "the-batman",
        title: "The Batman",
        description: "When a sadistic serial killer begins murdering key political figures in Gotham, Batman is forced to investigate the city's hidden corruption and question his family's involvement.",
        year: "2022",
        rating: 7.9,
        category: "Action",
        availableOn: ["Netflix", "Amazon Prime"],
        director: "Matt Reeves",
        cast: [
            { name: "Robert Pattinson", role: "Bruce Wayne / Batman" },
            { name: "Zoë Kravitz", role: "Selina Kyle / Catwoman" },
            { name: "Paul Dano", role: "Edward Nashton / The Riddler" }
        ],
        duration: 176
    },
    {
        id: "interstellar",
        title: "Interstellar",
        description: "A team of explorers travel through a wormhole in space in an attempt to ensure humanity's survival.",
        year: "2014",
        rating: 8.7,
        category: "Science Fiction",
        availableOn: ["Netflix", "Canal+"],
        director: "Christopher Nolan",
        cast: [
            { name: "Matthew McConaughey", role: "Cooper" },
            { name: "Anne Hathaway", role: "Brand" },
            { name: "Jessica Chastain", role: "Murph" }
        ],
        duration: 169
    },
    {
        id: "inception",
        title: "Inception",
        description: "A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O.",
        year: "2010",
        rating: 8.8,
        category: "Science Fiction",
        availableOn: ["Netflix"],
        director: "Christopher Nolan",
        cast: [
            { name: "Leonardo DiCaprio", role: "Cobb" },
            { name: "Joseph Gordon-Levitt", role: "Arthur" },
            { name: "Elliot Page", role: "Ariadne" }
        ],
        duration: 148
    },
    {
        id: "the-dark-knight",
        title: "The Dark Knight",
        description: "When the menace known as the Joker wreaks havoc and chaos on the people of Gotham, Batman must accept one of the greatest psychological and physical tests of his ability to fight injustice.",
        year: "2008",
        rating: 9.0,
        category: "Action",
        availableOn: ["Netflix", "HBO Max"],
        director: "Christopher Nolan",
        cast: [
            { name: "Christian Bale", role: "Bruce Wayne" },
            { name: "Heath Ledger", role: "Joker" },
            { name: "Aaron Eckhart", role: "Harvey Dent" }
        ],
        duration: 152
    },
     {
        id: "parasite",
        title: "Parasite",
        description: "Greed and class discrimination threaten the newly formed symbiotic relationship between the wealthy Park family and the destitute Kim clan.",
        year: "2019",
        rating: 8.5,
        category: "Thriller",
        availableOn: ["Amazon Prime"],
        director: "Bong Joon Ho",
        cast: [
            { name: "Kang-ho Song", role: "Ki Taek" },
            { name: "Sun-kyun Lee", role: "Dong Ik" },
            { name: "Yeo-jeong Cho", role: "Yeon Kyo" }
        ],
        duration: 132
    },
     {
        id: "avengers-endgame",
        title: "Avengers: Endgame",
        description: "After the devastating events of Avengers: Infinity War, the universe is in ruins. With the help of remaining allies, the Avengers assemble once more in order to reverse Thanos' actions and restore balance to the universe.",
        year: "2019",
        rating: 8.4,
        category: "Action",
        availableOn: ["Disney+"],
        director: "Anthony Russo, Joe Russo",
        cast: [
            { name: "Robert Downey Jr.", role: "Tony Stark / Iron Man" },
            { name: "Chris Evans", role: "Steve Rogers / Captain America" },
            { name: "Mark Ruffalo", role: "Bruce Banner / Hulk" }
        ],
        duration: 181
    },
    {
        id: "joker",
        title: "Joker",
        description: "In Gotham City, mentally troubled comedian Arthur Fleck is disregarded and mistreated by society. He then embarks on a downward spiral of revolution and bloody crime. This path brings him face-to-face with his alter-ego: the Joker.",
        year: "2019",
        rating: 8.4,
        category: "Drama",
        availableOn: ["Netflix"],
        director: "Todd Phillips",
        cast: [
             { name: "Joaquin Phoenix", role: "Arthur Fleck" },
             { name: "Robert De Niro", role: "Murray Franklin" },
             { name: "Zazie Beetz", role: "Sophie Dumond" }
        ],
        duration: 122
    },
    {
        id: "whiplash",
        title: "Whiplash",
        description: "A promising young drummer enrolls at a cut-throat music conservatory where his dreams of greatness are mentored by an instructor who will stop at nothing to realize a student's potential.",
        year: "2014",
        rating: 8.5,
        category: "Drama",
        availableOn: ["Netflix"],
        director: "Damien Chazelle",
        cast: [
            { name: "Miles Teller", role: "Andrew" },
            { name: "J.K. Simmons", role: "Fletcher" },
            { name: "Paul Reiser", role: "Jim Neiman" }
        ],
        duration: 106
    },
    {
        id: "a-quiet-place",
        title: "A Quiet Place",
        description: "In a post-apocalyptic world, a family is forced to live in silence while hiding from monsters with ultra-sensitive hearing.",
        year: "2018",
        rating: 7.5,
        category: "Horror",
        availableOn: ["Netflix", "Amazon Prime"],
        director: "John Krasinski",
        cast: [
            { name: "Emily Blunt", role: "Evelyn Abbott" },
            { name: "John Krasinski", role: "Lee Abbott" },
            { name: "Millicent Simmonds", role: "Regan Abbott" }
        ],
        duration: 90
    },
    {
        id: "my-neighbor-totoro",
        title: "My Neighbor Totoro",
        description: "When two girls move to the country to be near their ailing mother, they have adventures with the wondrous forest spirits who live nearby.",
        year: "1988",
        rating: 8.1,
        category: "Animation",
        availableOn: ["Netflix"],
        director: "Hayao Miyazaki",
        cast: [
            { name: "Hitoshi Takagi", role: "Totoro (voice)" },
            { name: "Noriko Hidaka", role: "Satsuki (voice)" },
            { name: "Chika Sakamoto", role: "Mei (voice)" }
        ],
        duration: 86
    },
    {
        id: "toy-story",
        title: "Toy Story",
        description: "A cowboy doll is profoundly threatened and jealous when a new spaceman figure supplants him as top toy in a boy's room.",
        year: "1995",
        rating: 8.3,
        category: "Animation",
        availableOn: ["Disney+"],
        director: "John Lasseter",
        cast: [
            { name: "Tom Hanks", role: "Woody (voice)" },
            { name: "Tim Allen", role: "Buzz Lightyear (voice)" },
            { name: "Don Rickles", role: "Mr. Potato Head (voice)" }
        ],
        duration: 81
    }
];

// Re-export dummyMovies for backward compatibility if needed, but prefer reusing baseMovies
export const dummyMovies = baseMovies.slice(0, 6);

// Export allMovies with duplicated content for pagination demo
// We map them to ensure unique IDs in the second batch if we wanted strict uniqueness, 
// but for simple display logic, keeping same IDs might be confusing for routing.
// Let's just double the list but append "-2" to IDs in the second batch to avoid key/routing conflicts.
export const allMovies: Movie[] = [
    ...baseMovies,
    ...baseMovies.map(m => ({ ...m, id: m.id + "-2" }))
];
