import { Link } from "react-router-dom";
import { ArrowUpRight, BookOpen } from "lucide-react";
import type { Classroom } from "../../../domain/models";
import { Badge } from "../../../shared/ui";
export function ClassCard({
  classroom,
  index = 0,
}: {
  classroom: Classroom;
  index?: number;
}) {
  return (
    <Link
      className={"class-card color-" + (index % 4)}
      to={"/app/classes/" + classroom.id}
    >
      <div className="class-cover">
        <span className="class-symbol">
          <BookOpen size={24} />
        </span>
        <ArrowUpRight size={21} />
        <span className="cover-circle" />
      </div>
      <div className="class-card-content">
        <Badge value={classroom.status} />
        <h3>{classroom.name}</h3>
        <p>
          {classroom.description || "Không gian học tập và trao đổi của lớp."}
        </p>
        <div className="class-card-foot">
          <span>{classroom.timezone}</span>
          <span>Vào lớp →</span>
        </div>
      </div>
    </Link>
  );
}
