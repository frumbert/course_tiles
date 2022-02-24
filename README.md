# course_tiles
Moodle block for displaying courses in tiles

The layout uses a template which displays courses as tiles in a grid. It also writes the users completion or enrolment status onto a data attribute for each tile which you can pick up in styles, as shown below.

![course-tiles](https://i.imgur.com/qoAtWl1.jpg)

This is a block plugin and belongs in the blocks folder. It works best in a theme that allows a center column. In my example I've used boost_campus on Moodle 3.6.4 with a custom area in the centre column. YMMV.

Sample stylesheet
-----------------
Implement this in your themes SCSS setting. Uses css grid.

.course-catalogue.course-catalogue-has-menu {
    display: grid;
    grid-auto-flow: row dense;
    grid-template-columns: 25% 1fr;
    grid-template-rows: min-content;
    gap: 0px 10px;
    grid-template-areas: 
        "menu category"
        "menu tiles";
}
.course-catalogue-category-tabs {
    grid-area: menu;
    a {
        display: block;
        border: 1px solid #1f3e7940;
        margin-bottom: 10px;
        padding: 0.5rem;
        border-radius: 4px;
        background-color: #fff;
        transition: all .3s;
        box-shadow: 0.125rem 0.125rem 0.25em rgb(0 0 0 / 12%);
        &:hover, &:active {
            background-color: #1f3e79;
            color: #fff;
            text-decoration: none;
        }
    }
}
.course-catalogue-category-description {
    grid-area: category;
}


.course-catalogue-course-tiles {
    grid-area: tiles;
	margin:0 auto;
	display: grid;
/*
	grid-template-columns: 1fr 1fr 1fr 1fr;
	grid-template-areas: ". . . .";

	next line see https://css-tricks.com/auto-sizing-columns-css-grid-auto-fill-vs-auto-fit/
*/
	grid-template-columns: repeat(auto-fit,minmax(300px, 1fr));
	grid-gap:10px;
	.course-tile {
		border:1px solid #eee;
		border-radius: 4px;
		background-color: white;
		transform: box-shadow .5s ease;
		box-shadow: 0 5px 10px rgba(0,0,0,0);
		display: flex;
		flex-direction: column;
		padding-bottom: 1em;
		&:hover {
			box-shadow: 0 5px 10px rgba(0,0,0,.25);
		}
		.courseimage img {
			height: 100%;
			width: 100%;
			object-fit: cover;
			border-radius: 5px 5px 0 0;
		}
		.tile-name {
			font-weight: bold;
			padding: 5px;
			font-size: 1.2em;
			color: #0da89e;
		}
		.tile-summary {
			flex: 1;
			padding: 5px;
			font-size: 1.1em;
		}
		.tile-button {
			text-align: center;
			button {
				cursor:pointer;
				background-color: #2d4277!important;
				border-radius: 5px;
				color: white;
				border: none;
				padding: .5em 1.5em;
				font-weight: normal;
				transition: background-color .3s ease;
				&:hover {
					background-color: #0da89e!important;
				}
				&.coming-soon-btn {
					background-color: #b8b8b8!important;
					&:hover {
						background-color: #c8c8c8!important;
					}
				}
			}
		}
		.tile-admin {
			text-align:center;
			margin: 1em 0 0;
			.action-btn {
				position:relative;
				border-radius: 50%;
				margin: 0 10px;
				border: none;
				background: none;
				padding-top: 3px;
				outline: none;
				.action-btn-hover-text {
				    position: absolute;
				    top: 2.5em;
				    padding:10px;
				    white-space:nowrap;
				    background-color: #939ba4;
				    border-radius: 5px;
				    color: white;
				    display:none;
				}
				&:hover {
					background-color:#eee;
					.action-btn-hover-text {
						display: block;
					}
				}
			}
		}
	}
}

Licence: GPL3, as per moodle.
